<?php
$types = [
    'autonomo' => 'Autônomo',
    'mei' => 'MEI',
    'simples' => 'PJ — Simples Nacional',
    'presumido' => 'PJ — Lucro Presumido',
    'real' => 'PJ — Lucro Real',
];

$initials = strtoupper(substr($data['nome'], 0, 1));

function toggleSetting($key, $title, $description, $data)
{
    ?>
<label class="setting-row">
    <span>
        <strong><?= e($title) ?></strong>
        <small><?= e($description) ?></small>
    </span>

    <input class="switch" type="checkbox" name="<?= e($key) ?>" value="1" <?= $data[$key] ?? 1 ? 'checked' : '' ?>>
</label>
<?php
}

ob_start();
?>

<link rel="stylesheet" href="assets/css/settings.css">

<div class="settings-shell">
    <aside class="sidebar">
        <a class="brand" href="?p=painel">
            <b>F</b> FLUXON
        </a>

        <nav aria-label="Menu principal">
            <a href="?p=painel">
                ⊞ &nbsp; Dashboard
            </a>

            <span>
                ↕ &nbsp; Fluxo de Caixa
                <small>Em breve</small>
            </span>

            <span>
                ◎ &nbsp; Reserva Fiscal
                <small>Em breve</small>
            </span>

            <span>
                ≡ &nbsp; Transações
                <small>Em breve</small>
            </span>

            <a class="active" href="?p=perfil" aria-current="page">
                ⚙ &nbsp; Configurações
            </a>
        </nav>

        <div class="sidebar-user">
            <b><?= e($initials) ?></b>

            <span>
                <?= e($data['nome']) ?>
                <small>
                    <?= e($data['ocupacao'] ?: 'Minha conta') ?>
                </small>
            </span>
        </div>
    </aside>

    <div class="workspace">
        <header class="topbar">
            <h1>Configurações</h1>

            <div>
                <a href="#notificacoes" aria-label="Preferências de notificações">
                    ♧
                </a>

                <a class="mini-avatar" href="#perfil" aria-label="Seu perfil">
                    <?= e($initials) ?>
                </a>
            </div>
        </header>

        <main class="settings-content">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">

                <section class="settings-card" id="perfil">
                    <h2>Perfil</h2>

                    <div class="identity">
                        <?php if ($data['tem_foto']): ?>
                        <img src="?p=foto" alt="Sua foto" width="48" height="48">
                        <?php else: ?>
                        <b><?= e($initials) ?></b>
                        <?php endif; ?>

                        <div>
                            <strong><?= e($data['nome']) ?></strong>

                            <small>
                                <?= e(
                                    $data['ocupacao'] ?:
                                    'Complete sua ocupação',
                                ) ?>
                                ·
                                <?= e(
                                    $types[
                                        $data['tipo_empresa'] ?? 'autonomo'
                                    ] ?? 'Autônomo',
                                ) ?>
                            </small>

                            <small><?= e($data['email']) ?></small>
                        </div>
                    </div>

                    <div class="fields">
                        <label>
                            Nome completo
                            <input name="nome" required maxlength="120" value="<?= e($data['nome']) ?>"
                                autocomplete="name">
                        </label>

                        <label>
                            E-mail
                            <input type="email" value="<?= e($data['email']) ?>" readonly
                                title="O e-mail de identificação não pode ser alterado">
                        </label>

                        <label>
                            CNPJ
                            <input name="cnpj" maxlength="18" placeholder="00.000.000/0000-00"
                                value="<?= e($data['cnpj'] ?? '') ?>">
                        </label>

                        <label>
                            Tipo de empresa
                            <select name="tipo_empresa">
                                <?php foreach ($types as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= ($data['tipo_empresa'] ??
                                            'autonomo') ===
                                        $value
                                            ? 'selected'
                                            : '' ?>>
                                    <?= e($label) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>

                    <details>
                        <summary>Foto e dados complementares</summary>

                        <div class="fields">
                            <label>
                                Telefone
                                <input name="telefone" type="tel" maxlength="20"
                                    value="<?= e($data['telefone'] ?? '') ?>">
                            </label>

                            <label>
                                Cidade
                                <input name="cidade" maxlength="100" value="<?= e($data['cidade'] ?? '') ?>">
                            </label>

                            <label>
                                Ocupação
                                <input name="ocupacao" maxlength="100" value="<?= e($data['ocupacao'] ?? '') ?>">
                            </label>

                            <label>
                                Foto de perfil
                                <input type="file" name="foto" accept="image/jpeg,image/png">
                            </label>
                        </div>

                        <small>
                            JPG ou PNG, até 2 MB e 3000 × 3000 pixels.
                        </small>

                        <?php if ($data['tem_foto']): ?>
                        <label class="check">
                            <input type="checkbox" name="remover_foto" value="1">
                            Remover foto atual
                        </label>
                        <?php endif; ?>
                    </details>

                    <button class="save" type="submit">
                        Salvar alterações
                    </button>
                </section>

                <section class="settings-card">
                    <h2>Configurações fiscais</h2>

                    <label class="rate">
                        Alíquota estimada (%)
                        <input id="aliquota" type="number" name="aliquota" min="0" max="100" step="0.01" required
                            value="<?= e($data['aliquota'] ?? '14') ?>">
                    </label>

                    <small>
                        Valor de referência para a reserva de cada receita.
                    </small>

                    <?php toggleSetting(
                        'reserva_auto',
                        'Separação automática de reserva',
                        'Reservar a alíquota definida em cada entrada.',
                        $data,
                    ); ?>

                    <small class="note">
                        A automação depende da integração com o módulo financeiro.
                    </small>

                    <button class="save" type="submit">
                        Salvar configurações fiscais
                    </button>
                </section>

                <section class="settings-card" id="notificacoes">
                    <h2>Notificações</h2>

                    <?php
                    toggleSetting(
                        'alertas_vencimento',
                        'Alertas de vencimento',
                        'Avisar 3 dias antes de impostos e contas.',
                        $data,
                    );

                    toggleSetting(
                        'lembretes_cobranca',
                        'Lembretes de cobrança',
                        'Lembrar de cobrar clientes com atraso.',
                        $data,
                    );
                    ?>

                    <details>
                        <summary>Canais e frequência</summary>

                        <?php
                        toggleSetting(
                            'notif_app',
                            'No aplicativo',
                            'Receber alertas dentro do Fluxon.',
                            $data,
                        );

                        toggleSetting(
                            'notif_email',
                            'Por e-mail',
                            'Usar o e-mail da conta.',
                            $data,
                        );
                        ?>

                        <label>
                            Frequência
                            <select id="frequencia" name="frequencia">
                                <?php $frequencias = [
                                    'evento' => 'Por evento',
                                    'diario' => 'Diariamente',
                                    'programado' => 'Programado diariamente',
                                ]; ?>

                                <?php foreach (
                                    $frequencias
                                    as $value => $label
                                ): ?>
                                <option value="<?= e($value) ?>" <?= ($data['frequencia'] ??
                                            'evento') ===
                                        $value
                                            ? 'selected'
                                            : '' ?>>
                                    <?= e($label) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label>
                            Horário de Brasília
                            <input type="time" id="horario" name="horario" value="<?= e(
                                    substr($data['horario'] ?? '', 0, 5),
                                ) ?>">
                        </label>
                    </details>

                    <small class="note">
                        As preferências são salvas. O envio de alertas
                        financeiros ainda não está integrado.
                    </small>

                    <button class="save" type="submit">
                        Salvar preferências
                    </button>
                </section>

                <section class="plan">
                    <small>Plano ilustrativo</small>
                    <span class="badge">Demo</span>

                    <h2>Fluxon Pro</h2>

                    <p>R$ 49,90/mês · Exemplo de assinatura</p>

                    <button type="button" id="manage-plan">
                        Gerenciar assinatura
                    </button>

                    <p id="plan-info" hidden>
                        Este projeto ainda não possui cobrança
                        ou assinatura integrada.
                    </p>
                </section>
            </form>
            <section class="settings-card" style="margin-top:16px">
                <h2>Outras configurações</h2>

                <nav aria-label="Outras configurações" style="display:grid;gap:14px;font-size:14px">
                    <a href="?p=seguranca">Segurança e senha</a>
                    <a href="?p=suporte">Ajuda e contato</a>
                    <a href="?p=faq">Perguntas frequentes</a>
                    <a href="?p=sobre">Sobre o aplicativo</a>
                    <a href="?p=avaliacao">Avaliar aplicativo</a>
                    <a href="?p=acessibilidade">Acessibilidade</a>
                </nav>
            </section>
            <section class="settings-card" style="margin-top:16px">
                <h2 style="color:#a32020">Exclusão de conta</h2>

                <p>
                    Manter sua conta preserva seu perfil, sua foto,
                    suas configurações fiscais e suas preferências.
                </p>

                <p>
                    Na próxima tela, você poderá consultar os detalhes,
                    cancelar ou solicitar um código por e-mail.
                    Clicar abaixo não exclui sua conta.
                </p>

                <a class="save" href="?p=excluir-conta"
                    style="display:inline-block;text-decoration:none;background:#a32020;color:#fff">
                    Revisar opções de exclusão
                </a>
            </section>

            <a class="logout" href="?p=painel">
                Voltar ao painel
            </a>
        </main>
    </div>
</div>

<script src="assets/js/settings.js" defer></script>

<?php view('Configurações', ob_get_clean());
