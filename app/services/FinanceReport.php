<?php

final class FinanceReport
{
    public const LIMIT = 10000;

    public static function money(int|string $cents): string
    {
        $n = (int) $cents;
        return ($n < 0 ? '−' : '') . 'R$ ' . number_format(abs($n) / 100, 2, ',', '.');
    }

    public static function generatedAt(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo')))->format('d/m/Y H:i T');
    }

    public static function cells(array $r): array
    {
        return [
            date('d/m/Y', strtotime($r['occurred_on'])), $r['description'], $r['account'],
            $r['category'], $r['kind'] === 'entrada' ? 'Entrada' : 'Saída',
            $r['reversed_at'] ? 'Estornada' : 'Confirmada', (int) $r['amount_cents'],
        ];
    }

    private static function pdfText(string $value): string
    {
        $value = str_replace('−','-',$value);
        $value = iconv('UTF-8','Windows-1252//TRANSLIT//IGNORE',$value);
        return strtr($value === false ? '' : $value, ['\\'=>'\\\\','('=>'\\(',')'=>'\\)']);
    }

    public static function pdf(iterable $entries, array $meta): string
    {
        $objects = [1=>'<< /Type /Catalog /Pages 2 0 R >>',2=>'',
            3=>'<< /Type /Font /Subtype /Type1 /BaseFont /Courier /Encoding /WinAnsiEncoding >>'];
        $pages = [];
        $y = 0;
        $stream = '';
        $text = static function (string $line, int $x, int $top, int $size) use (&$stream): void {
            $stream .= 'BT /F1 ' . $size . ' Tf 1 0 0 1 ' . $x . ' ' . $top . ' Tm (' . self::pdfText($line) . ") Tj ET\n";
        };
        $finish = static function () use (&$objects,&$pages,&$stream): void {
            $pageId = count($objects) + 1;
            $streamId = $pageId + 1;
            $objects[$pageId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents $streamId 0 R >>";
            $objects[$streamId] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . 'endstream';
            $pages[] = "$pageId 0 R";
            $stream = '';
        };
        $wrap = static function (string $value,int $limit): array {
            $length = static fn($text) => count(preg_split('//u',$text,-1,PREG_SPLIT_NO_EMPTY) ?: []);
            $lines = [];
            $line = '';
            foreach (preg_split('/ +/u',$value) ?: [] as $word) {
                if ($length($line.($line!==''?' ':'').$word) <= $limit) {
                    $line .= ($line!==''?' ':'').$word;
                    continue;
                }
                if ($line !== '') {
                    $lines[] = $line;
                    $line = '';
                }
                $chars = preg_split('//u',$word,-1,PREG_SPLIT_NO_EMPTY) ?: [];
                while (count($chars)>$limit) {
                    $lines[] = implode('',array_splice($chars,0,$limit));
                }
                $line = implode('',$chars);
            }
            if ($line !== '') $lines[] = $line;
            return $lines;
        };
        $start = static function (bool $first) use (&$y,$text,&$pages,$meta,$wrap): void {
            $text('Fluxon Analytics | Relatório de movimentações',42,801,16);
            $text('Gerado em ' . $meta['date'],42,778,10);
            $text('Página ' . (count($pages)+1),42,28,9);
            $y = 758;
            if ($first) {
                foreach ([$meta['filters'],$meta['totals']] as $summary) {
                    foreach ($wrap($summary,82) as $line) {
                        $text($line,42,$y,10);
                        $y -= 15;
                    }
                }
                $y -= 12;
            }
            $text('Data, tipo, situação e valor',42,$y,11);
            $text('Descrição / conta / categoria',240,$y,11);
            $y -= 23;
        };
        $start(true);
        $any = false;
        foreach ($entries as $row) {
            $any = true;
            [$date,$description,$account,$category,$kind,$status,$amount] = self::cells($row);
            $details = $wrap($description . ' | ' . $account . ' | ' . $category,51);
            $height = max(count($details),3)*13+9;
            if ($y-$height<48) {
                $finish();
                $start(false);
            }
            $text($date.' | '.$kind,42,$y,10);
            $text($status,42,$y-13,10);
            $text(self::money($amount),42,$y-26,10);
            foreach ($details as $i=>$line) {
                $text($line,240,$y-$i*13,10);
            }
            $y -= $height;
        }
        if (!$any) {
            $text('Nenhuma movimentação para os filtros informados.',42,$y,10);
        }
        $finish();
        $objects[2] = '<< /Type /Pages /Count ' . count($pages) . ' /Kids [' . implode(' ',$pages) . '] >>';
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $id=>$object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "$id 0 obj\n$object\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= 'xref' . "\n0 " . count($offsets) . "\n0000000000 65535 f \n";
        foreach (array_slice($offsets,1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n",$offset);
        }
        return $pdf.'trailer << /Size '.count($offsets)." /Root 1 0 R >>\nstartxref\n$xref\n%%EOF\n";
    }

    public static function xlsx(iterable $entries, array $meta): string
    {
        if (!class_exists('ZipArchive')) {
            throw new DomainException('Ative a extensão ZIP do PHP para exportar em XLSX.');
        }
        $tmp = tempnam(sys_get_temp_dir(),'fluxon-xlsx-');
        if ($tmp === false) {
            throw new RuntimeException('Não foi possível criar o relatório temporário.');
        }
        $zip = new ZipArchive();
        try {
            if ($zip->open($tmp,ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Não foi possível criar a planilha.');
            }
            $escape = static fn($v) => htmlspecialchars((string) $v, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE,'UTF-8');
            $rowNumber = 0;
            $rowXml = static function (array $cells, bool $numeric = false) use (&$rowNumber,$escape): string {
                $n = ++$rowNumber;
                $xml = "<row r=\"$n\"".($n===3?' ht="45" customHeight="1"':'').">";
                foreach (array_values($cells) as $i=>$cell) {
                    $ref = chr(65+$i).$n;
                    if ($numeric && $i === 6) {
                        $xml .= '<c r="'.$ref.'" s="4"><v>'.number_format($cell/100,2,'.','').'</v></c>';
                    } else {
                        $style = $n===1 ? 1 : ($n<=4 ? 2 : ($n===6 ? 3 : 0));
                        $xml .= '<c r="'.$ref.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.$escape($cell).'</t></is></c>';
                    }
                }
                return $xml.'</row>';
            };
            $s = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
            $rel = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
            $rels = 'http://schemas.openxmlformats.org/package/2006/relationships';
            $xml = '<?xml version="1.0" encoding="UTF-8"?>';
            $sheet = $xml.'<worksheet xmlns="'.$s.'"><cols><col min="1" max="1" width="16" customWidth="1"/><col min="2" max="2" width="52" customWidth="1"/><col min="3" max="4" width="29" customWidth="1"/><col min="5" max="7" width="16" customWidth="1"/></cols><sheetData>';
            foreach ([['Fluxon Analytics | Relatório de movimentações'],['Gerado em '.$meta['date']],[$meta['filters']],[$meta['totals']],[],['Data','Descrição','Conta','Categoria','Tipo','Situação','Valor (R$)']] as $row) {
                $sheet .= $rowXml($row);
            }
            foreach ($entries as $entry) {
                $sheet .= $rowXml(self::cells($entry),true);
            }
            $sheet .= '</sheetData><autoFilter ref="A6:G'.max(6,$rowNumber).'"/><mergeCells count="4"><mergeCell ref="A1:G1"/><mergeCell ref="A2:G2"/><mergeCell ref="A3:G3"/><mergeCell ref="A4:G4"/></mergeCells></worksheet>';
            $files = [
                '[Content_Types].xml'=>$xml.'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>',
                '_rels/.rels'=>$xml.'<Relationships xmlns="'.$rels.'"><Relationship Id="rId1" Type="'.$rel.'/officeDocument" Target="xl/workbook.xml"/></Relationships>',
                'xl/styles.xml'=>$xml.'<styleSheet xmlns="'.$s.'"><numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0.00"/></numFmts><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="12"/><name val="Calibri"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="5"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment wrapText="1" vertical="top"/></xf><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0"/><xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>',
                'xl/workbook.xml'=>$xml.'<workbook xmlns="'.$s.'" xmlns:r="'.$rel.'"><sheets><sheet name="Movimentações" sheetId="1" r:id="rId1"/></sheets></workbook>',
                'xl/_rels/workbook.xml.rels'=>$xml.'<Relationships xmlns="'.$rels.'"><Relationship Id="rId1" Type="'.$rel.'/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="'.$rel.'/styles" Target="styles.xml"/></Relationships>',
                'xl/worksheets/sheet1.xml'=>$sheet,
            ];
            foreach ($files as $name=>$value) {
                if (!$zip->addFromString($name,$value)) {
                    throw new RuntimeException('Não foi possível escrever a planilha.');
                }
            }
            if (!$zip->close() || ($result = file_get_contents($tmp)) === false) {
                throw new RuntimeException('Não foi possível finalizar a planilha.');
            }
            return $result;
        } finally {
            @unlink($tmp);
        }
    }
}
