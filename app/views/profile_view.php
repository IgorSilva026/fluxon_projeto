<?php
$types = [
    'autonomo' => 'Autônomo',
    'mei' => 'MEI',
    'simples' => 'PJ — Simples Nacional',
    'presumido' => 'PJ — Lucro Presumido',
    'real' => 'PJ — Lucro Real',
];

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

<?php
$shellName = $data['nome'];
$shellTitle = 'Configurações';
require __DIR__ . '/dashboard_header.php';
?>
        <main class="dashboard-content profile-content">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">

                <section class="settings-card" id="perfil">
                    <h2>Perfil</h2>

                    <div class="identity">
                        <?php if ($data['tem_foto']): ?>
                        <img src="?p=foto" alt="Sua foto" width="48" height="48">
                        <?php else: ?>
                        <b><?= e($shellInitial) ?></b>
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
                        Referência fiscal do perfil. Não determina o cálculo das regras financeiras.
                    </small>

                    <?php toggleSetting(
                        'reserva_auto',
                        'Preferência de reserva automática',
                        'Preferência do perfil; o cálculo usa as regras cadastradas no módulo financeiro.',
                        $data,
                    ); ?>

                    <small class="note">
                        Para aplicar percentuais às entradas, configure <a href="?p=cadastros&amp;grupo=regras">Regras de provisão</a>. As regras financeiras são independentes desta referência fiscal.
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
            <section class="settings-card">
                <h2>Outras configurações</h2>

                <nav aria-label="Outras configurações" class="profile-links">
                    <a href="?p=seguranca">Segurança e senha</a>
                    <a href="?p=suporte">Ajuda e contato</a>
                    <a href="?p=faq">Perguntas frequentes</a>
                    <a href="?p=sobre">Sobre o aplicativo</a>
                    <a href="?p=avaliacao">Avaliar aplicativo</a>
                    <a href="?p=acessibilidade">Acessibilidade</a>
                </nav>
            </section>
            <section class="settings-card">
                <h2>Exclusão de conta</h2>

                <p>
                    Manter sua conta preserva seu perfil, sua foto,
                    suas configurações fiscais e suas preferências.
                </p>

                <p>
                    Na próxima tela, você poderá consultar os detalhes,
                    cancelar ou solicitar um código por e-mail.
                    Clicar abaixo não exclui sua conta.
                </p>

                <a class="save profile-danger" href="?p=excluir-conta">
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
