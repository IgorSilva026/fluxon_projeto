<?php
final class ProfileService
{
    public static function validate(array $in): array
    {
        $v = [];
        foreach (
            [
                'nome' => 120,
                'telefone' => 20,
                'cidade' => 100,
                'ocupacao' => 100,
                'cnpj' => 18,
            ]
            as $key => $max
        ) {
            if (isset($in[$key]) && !is_string($in[$key])) {
                throw new InvalidArgumentException(
                    'Formato de campo inválido.',
                );
            }
            $v[$key] = trim($in[$key] ?? '');
            if (
                !preg_match('//u', $v[$key]) ||
                preg_match_all('/./us', $v[$key]) > $max
            ) {
                throw new InvalidArgumentException(
                    'Confira o tamanho e os caracteres dos campos.',
                );
            }
        }
        if ($v['nome'] === '') {
            throw new InvalidArgumentException('Informe seu nome.');
        }
        if (
            $v['telefone'] !== '' &&
            !preg_match('/^[0-9+() .-]{8,20}$/D', $v['telefone'])
        ) {
            throw new InvalidArgumentException(
                'Informe um telefone válido ou deixe o campo vazio.',
            );
        }
        foreach (['notif_app', 'notif_email'] as $k) {
            $v[$k] = isset($in[$k]) && $in[$k] === '1' ? 1 : 0;
        }
        $v['frequencia'] = $in['frequencia'] ?? 'evento';
        if (
            !is_string($v['frequencia']) ||
            !in_array(
                $v['frequencia'],
                ['evento', 'diario', 'programado'],
                true,
            )
        ) {
            throw new InvalidArgumentException(
                'Escolha uma frequência válida.',
            );
        }
        $h = $in['horario'] ?? '';
        if (!is_string($h)) {
            throw new InvalidArgumentException('Horário inválido.');
        }
        if (
            $v['frequencia'] !== 'evento' &&
            !preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D', $h)
        ) {
            throw new InvalidArgumentException(
                'Escolha o horário para receber as notificações.',
            );
        }
        $v['horario'] = $v['frequencia'] === 'evento' ? null : $h;
        if (
            $v['cnpj'] !== '' &&
            !preg_match(
                '/^(?:[0-9]{14}|[0-9]{2}\.[0-9]{3}\.[0-9]{3}\/[0-9]{4}-[0-9]{2})$/D',
                $v['cnpj'],
            )
        ) {
            throw new InvalidArgumentException(
                'Informe o CNPJ com 14 números.',
            );
        }
        $v['tipo_empresa'] = $in['tipo_empresa'] ?? 'autonomo';
        if (
            !is_string($v['tipo_empresa']) ||
            !in_array(
                $v['tipo_empresa'],
                ['autonomo', 'mei', 'simples', 'presumido', 'real'],
                true,
            )
        ) {
            throw new InvalidArgumentException('Tipo de empresa inválido.');
        }
        $rate = $in['aliquota'] ?? '14';
        if (
            !is_string($rate) ||
            !is_numeric(str_replace(',', '.', $rate)) ||
            (float) str_replace(',', '.', $rate) < 0 ||
            (float) str_replace(',', '.', $rate) > 100
        ) {
            throw new InvalidArgumentException(
                'A alíquota deve estar entre 0 e 100%.',
            );
        }
        $v['aliquota'] = round((float) str_replace(',', '.', $rate), 2);
        foreach (
            ['reserva_auto', 'alertas_vencimento', 'lembretes_cobranca']
            as $k
        ) {
            $v[$k] = ($in[$k] ?? '') === '1' ? 1 : 0;
        }
        // Campos enviados como email, cpf, user_id e id nunca são persistidos.
        return $v;
    }
    public static function photo(array $file): ?string
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException(
                'Falha no upload. Escolha novamente a imagem.',
            );
        }
        $path = $file['tmp_name'] ?? '';
        if (
            !is_string($path) ||
            !is_uploaded_file($path) ||
            filesize($path) > 2 * 1024 * 1024
        ) {
            throw new InvalidArgumentException('A imagem deve ter até 2 MB.');
        }
        if (!extension_loaded('gd')) {
            throw new InvalidArgumentException(
                'O envio de foto requer a extensão GD do PHP. Contate o responsável pelo sistema.',
            );
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
        $size = @getimagesize($path);
        if (
            !in_array($mime, ['image/jpeg', 'image/png'], true) ||
            !$size ||
            $size[0] > 3000 ||
            $size[1] > 3000 ||
            $size[0] * $size[1] > 9000000
        ) {
            throw new InvalidArgumentException(
                'Use JPG ou PNG com no máximo 3000 × 3000 pixels.',
            );
        }
        $source = @imagecreatefromstring(file_get_contents($path));
        if (!$source) {
            throw new InvalidArgumentException(
                'Não foi possível abrir essa imagem.',
            );
        }
        $scale = min(1, 512 / max($size[0], $size[1]));
        $w = max(1, (int) ($size[0] * $scale));
        $h = max(1, (int) ($size[1] * $scale));
        $dest = imagecreatetruecolor($w, $h);
        $white = imagecolorallocate($dest, 255, 255, 255);
        imagefill($dest, 0, 0, $white);
        imagecopyresampled(
            $dest,
            $source,
            0,
            0,
            0,
            0,
            $w,
            $h,
            $size[0],
            $size[1],
        );
        ob_start();
        imagejpeg($dest, null, 85);
        $out = ob_get_clean();
        imagedestroy($source);
        imagedestroy($dest);
        if (!is_string($out)) {
            throw new RuntimeException('Falha ao processar imagem.');
        }
        return $out; // Reencodificação descarta metadados e conteúdo anexado ao upload.
    }
}
