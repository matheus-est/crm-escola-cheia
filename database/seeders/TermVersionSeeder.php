<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\TermVersion;
use App\Models\User;
use Illuminate\Database\Seeder;

class TermVersionSeeder extends Seeder
{
    public function run(): void
    {
        if (TermVersion::where('version', '1.0')->exists()) {
            $this->command->info('TermVersion 1.0 already exists. Skipping.');

            return;
        }

        $admin = User::whereHas('role', fn ($q) => $q->where('name', 'Master'))
            ->first()
            ?? User::first();

        TermVersion::create([
            'version' => '1.0',
            'title' => 'Termos de Uso e Política de Privacidade',
            'content' => $this->getContent(),
            'is_active' => true,
            'effective_at' => now(),
            'created_by' => $admin?->id,
        ]);

        TermVersion::where('is_active', true)
            ->where('version', '!=', '1.0')
            ->update(['is_active' => false]);

        $this->command->info('TermVersion 1.0 created and activated.');
    }

    private function getContent(): string
    {
        return <<<'HTML'
<h1>Termos de Uso e Política de Privacidade</h1>
<p>Versão vigente a partir de <strong>15 de março de 2026</strong>.</p>
<p>Leia atentamente este documento antes de utilizar o sistema. Ao acessar ou utilizar a plataforma, você declara ter lido, compreendido e concordado integralmente com os termos aqui estabelecidos. Caso não concorde com qualquer disposição, não utilize o sistema e entre em contato com o administrador responsável.</p>
<hr>
<h2>1. Identificação do Controlador</h2>
<p>Para fins da Lei Geral de Proteção de Dados Pessoais (Lei nº 13.709/2018 — LGPD), o <strong>controlador dos dados pessoais</strong> tratados nesta plataforma é <strong>{{company_name}}</strong>, identificada no contrato de licença ou prestação de serviços firmado com o usuário ou sua empresa.</p>
<p>O encarregado pelo tratamento de dados (DPO) pode ser contatado pelo e-mail: <a href="mailto:{{dpo_email}}">{{dpo_email}}</a>.</p>
<hr>
<h2>2. Definições</h2>
<p>Para fins deste documento, adotam-se as seguintes definições:</p>
<ul>
  <li><strong>Sistema:</strong> plataforma de gestão administrativa acessada mediante credenciais, objeto destes termos.</li>
  <li><strong>Usuário:</strong> pessoa física identificada que acessa o sistema com login e senha pessoais.</li>
  <li><strong>Administrador:</strong> usuário com perfil de acesso total, responsável pela gestão de outros usuários e permissões.</li>
  <li><strong>Dado pessoal:</strong> qualquer informação relacionada a pessoa natural identificada ou identificável (art. 5º, I, LGPD).</li>
  <li><strong>Dado sensível:</strong> dado pessoal sobre origem racial ou étnica, convicção religiosa, opinião política, filiação sindical, dado de saúde ou vida sexual, dado genético ou biométrico (art. 5º, II, LGPD).</li>
  <li><strong>Tratamento:</strong> toda operação realizada com dados pessoais, como coleta, armazenamento, uso, acesso, transmissão ou eliminação (art. 5º, X, LGPD).</li>
  <li><strong>Controlador:</strong> organização que decide sobre o tratamento dos dados pessoais.</li>
  <li><strong>Log de auditoria:</strong> registro automático e imutável de ações realizadas no sistema, com identificação do usuário, data, hora e IP.</li>
</ul>
<hr>
<h2>3. Credenciais de Acesso e Responsabilidades do Usuário</h2>
<p>3.1. O acesso ao sistema é realizado por meio de <strong>login e senha pessoais e intransferíveis</strong>. Cada usuário é individualmente responsável por todas as ações realizadas com suas credenciais.</p>
<p>3.2. É expressamente <strong>proibido</strong>:</p>
<ul>
  <li>Compartilhar login ou senha com terceiros, incluindo colegas de trabalho;</li>
  <li>Utilizar credenciais de outro usuário, ainda que com autorização verbal;</li>
  <li>Deixar sessões ativas em dispositivos desatendidos;</li>
  <li>Tentar acessar funcionalidades ou dados para os quais não possui autorização;</li>
  <li>Realizar engenharia reversa, tentativas de invasão ou exploração de vulnerabilidades;</li>
  <li>Utilizar o sistema para finalidades diversas das autorizadas pela organização.</li>
</ul>
<p>3.3. O usuário deve <strong>comunicar imediatamente</strong> ao administrador qualquer suspeita de comprometimento, acesso indevido ou uso não autorizado de suas credenciais.</p>
<p>3.4. A senha deve atender aos requisitos mínimos de segurança estabelecidos pelo sistema: mínimo de 8 caracteres, contendo letras maiúsculas, minúsculas, números e caracteres especiais.</p>
<p>3.5. O sistema pode encerrar sessões inativas automaticamente, exigir troca periódica de senha ou bloquear o acesso após tentativas sucessivas de autenticação com falha, sem necessidade de aviso prévio.</p>
<hr>
<h2>4. Dados Pessoais Coletados e Finalidades do Tratamento</h2>
<p>4.1. Para a operação da plataforma, são coletados e tratados os seguintes dados pessoais dos usuários:</p>
<ul>
  <li><strong>Nome completo</strong> — identificação do usuário nas operações do sistema;</li>
  <li><strong>Endereço de e-mail</strong> — autenticação, comunicações e recuperação de acesso;</li>
  <li><strong>Senha (armazenada em hash irreversível)</strong> — autenticação segura;</li>
  <li><strong>Endereço IP</strong> — logs de auditoria e segurança;</li>
  <li><strong>Agente de navegação (User-Agent)</strong> — identificação do dispositivo de acesso para auditoria;</li>
  <li><strong>Data e hora de acesso</strong> — controle de sessões e auditoria;</li>
  <li><strong>Registro de ações realizadas</strong> — rastreabilidade e conformidade.</li>
</ul>
<p>4.2. O sistema <strong>não coleta</strong> dados sensíveis conforme definido no art. 5º, II da LGPD, salvo se expressamente configurado pela organização contratante para módulos específicos, hipótese em que bases legais adicionais serão aplicadas.</p>
<p>4.3. As bases legais para o tratamento dos dados são:</p>
<ul>
  <li><strong>Execução de contrato</strong> (art. 7º, V, LGPD) — para viabilizar o acesso e uso da plataforma;</li>
  <li><strong>Legítimo interesse</strong> (art. 7º, IX, LGPD) — para segurança, prevenção de fraudes e auditoria;</li>
  <li><strong>Cumprimento de obrigação legal</strong> (art. 7º, II, LGPD) — para manutenção de logs exigidos por normas aplicáveis;</li>
  <li><strong>Consentimento</strong> (art. 7º, I, LGPD) — formalizado pelo aceite destes termos, para finalidades adicionais quando aplicável.</li>
</ul>
<hr>
<h2>5. Registro de Atividades e Auditoria</h2>
<p>5.1. O sistema registra automaticamente todas as operações relevantes realizadas pelos usuários, incluindo:</p>
<ul>
  <li>Data, hora e fuso horário de cada acesso;</li>
  <li>Endereço IP do dispositivo utilizado;</li>
  <li>Navegador, sistema operacional e versão (User-Agent);</li>
  <li>Criação, edição e exclusão de registros;</li>
  <li>Alterações de permissões e perfis de acesso;</li>
  <li>Aceite de termos de uso (versão aceita, data, IP e User-Agent).</li>
</ul>
<p>5.2. Esses registros são mantidos por um prazo mínimo de <strong>180 dias</strong>, contados da data do evento registrado, podendo ser estendidos por exigência legal, investigação em curso ou determinação judicial. Durante esse período, podem ser utilizados em investigações internas, procedimentos administrativos ou processos judiciais.</p>
<p>5.3. O usuário declara ciência de que <strong>não existe expectativa de privacidade</strong> sobre as ações realizadas no sistema, que é um ambiente corporativo sujeito a monitoramento legítimo pela organização.</p>
<p>5.4. Os logs de auditoria são protegidos contra alteração ou exclusão não autorizada. O administrador não pode modificar registros de ações de outros usuários.</p>
<hr>
<h2>6. Controle de Acesso e Permissões (ACL)</h2>
<p>6.1. O acesso às funcionalidades da plataforma é controlado por um sistema de perfis e permissões (ACL — Access Control List), administrado pela organização.</p>
<p>6.2. Cada usuário possui permissões específicas atribuídas pelo administrador, condizentes com suas atribuições profissionais.</p>
<p>6.3. O acesso a dados e funcionalidades é restrito ao estritamente necessário para o exercício das funções do usuário, em conformidade com o princípio da necessidade (art. 6º, III, LGPD).</p>
<p>6.4. Tentativas de acesso a funcionalidades não autorizadas são registradas e podem resultar em suspensão ou encerramento do acesso.</p>
<hr>
<h2>7. Direitos dos Titulares de Dados</h2>
<p>7.1. Em conformidade com o art. 18 da LGPD, o usuário, na qualidade de titular de dados pessoais, tem direito a:</p>
<ul>
  <li><strong>Confirmação</strong> da existência de tratamento de seus dados;</li>
  <li><strong>Acesso</strong> aos dados pessoais tratados;</li>
  <li><strong>Correção</strong> de dados incompletos, inexatos ou desatualizados;</li>
  <li><strong>Anonimização, bloqueio ou eliminação</strong> de dados desnecessários ou tratados em desconformidade com a LGPD;</li>
  <li><strong>Portabilidade</strong> dos dados a outro fornecedor, mediante requisição formal;</li>
  <li><strong>Eliminação</strong> dos dados tratados com base no consentimento, ressalvadas as hipóteses de retenção legal;</li>
  <li><strong>Informação</strong> sobre compartilhamento de dados com terceiros;</li>
  <li><strong>Revogação do consentimento</strong>, a qualquer momento, mediante manifestação expressa.</li>
</ul>
<p>7.2. O exercício dos direitos previstos nesta cláusula deve ser realizado mediante solicitação formal ao encarregado de dados (DPO) pelo e-mail <a href="mailto:{{dpo_email}}">{{dpo_email}}</a>, que responderá no prazo legal.</p>
<p>7.3. A eliminação de dados pode ser limitada quando sua conservação for necessária para cumprimento de obrigação legal, exercício regular de direitos em processo judicial, administrativo ou arbitral, ou outras hipóteses previstas no art. 16 da LGPD.</p>
<hr>
<h2>8. Compartilhamento de Dados com Terceiros</h2>
<p>8.1. Os dados pessoais tratados nesta plataforma <strong>não são comercializados</strong> nem compartilhados com terceiros para finalidades de marketing ou publicidade.</p>
<p>8.2. O compartilhamento de dados pode ocorrer nas seguintes hipóteses:</p>
<ul>
  <li>Com operadores de infraestrutura tecnológica (servidores, nuvem) que processam dados sob instrução do controlador e com garantias contratuais de segurança;</li>
  <li>Por determinação judicial, ordem de autoridade competente ou obrigação legal;</li>
  <li>Para proteção da vida ou segurança do titular ou de terceiros;</li>
  <li>Mediante consentimento expresso do titular.</li>
</ul>
<p>8.3. Quando aplicável, os terceiros que recebem dados pessoais estão sujeitos a obrigações contratuais de confidencialidade e segurança compatíveis com a LGPD.</p>
<hr>
<h2>9. Segurança da Informação</h2>
<p>9.1. A plataforma adota medidas técnicas e organizacionais adequadas para proteger os dados pessoais contra acesso não autorizado, destruição, perda, alteração ou divulgação indevida, incluindo:</p>
<ul>
  <li>Armazenamento de senhas com hash criptográfico irreversível (bcrypt);</li>
  <li>Transmissão de dados via protocolo HTTPS/TLS;</li>
  <li>Controle de acesso baseado em perfis e permissões granulares;</li>
  <li>Registro imutável de auditoria de todas as operações sensíveis;</li>
  <li>Sessões com expiração automática por inatividade.</li>
</ul>
<p>9.2. Nenhum sistema é absolutamente seguro. Em caso de incidente de segurança que possa acarretar risco ou dano relevante aos titulares, a organização adotará as medidas previstas no art. 48 da LGPD, notificando a Autoridade Nacional de Proteção de Dados (ANPD) e os titulares afetados no prazo cabível.</p>
<hr>
<h2>10. Retenção e Eliminação de Dados</h2>
<p>10.1. Os dados pessoais são conservados pelo tempo necessário para as finalidades para as quais foram coletados, ou pelo prazo determinado por obrigação legal.</p>
<p>10.2. Prazos de retenção orientativos:</p>
<ul>
  <li><strong>Logs de auditoria de ações e acessos:</strong> 180 dias a partir do evento registrado, podendo ser estendido por exigência legal, investigação em curso ou determinação judicial;</li>
  <li><strong>Registros de aceite de termos de uso:</strong> pelo período de vigência do contrato acrescido de 5 anos, em conformidade com o prazo prescricional civil (art. 205, CC);</li>
  <li><strong>Dados cadastrais do usuário:</strong> enquanto a conta estiver ativa e por até 5 anos após o encerramento, salvo obrigação legal diversa.</li>
</ul>
<p>10.3. Após o término do prazo de retenção, os dados são eliminados de forma segura ou anonimizados, de modo a não permitir a identificação do titular.</p>
<hr>
<h2>11. Responsabilidades e Limitação de Responsabilidade</h2>
<p>11.1. O usuário é inteiramente responsável por:</p>
<ul>
  <li>Toda e qualquer ação realizada com suas credenciais de acesso;</li>
  <li>Danos causados ao sistema, a outros usuários ou a terceiros em decorrência de uso indevido;</li>
  <li>Violações a estas disposições que resultem em sanções administrativas, civis ou penais.</li>
</ul>
<p>11.2. A organização responsável pela plataforma não se responsabiliza por:</p>
<ul>
  <li>Danos decorrentes de uso indevido das credenciais pelo próprio usuário ou por terceiros em razão de negligência do usuário;</li>
  <li>Indisponibilidade do sistema por manutenção programada ou eventos fora do controle razoável;</li>
  <li>Perda de dados causada por ação do próprio usuário.</li>
</ul>
<p>11.3. A organização pode suspender ou encerrar o acesso de qualquer usuário que viole estas disposições, sem necessidade de aviso prévio, preservados os direitos de defesa em processo administrativo interno.</p>
<hr>
<h2>12. Uso Aceitável e Condutas Proibidas</h2>
<p>12.1. O sistema deve ser utilizado exclusivamente para as finalidades profissionais autorizadas pela organização.</p>
<p>12.2. É expressamente vedado:</p>
<ul>
  <li>Utilizar o sistema para atividades ilícitas ou contrárias à moral e à ordem pública;</li>
  <li>Inserir, transmitir ou armazenar dados falsos, fraudulentos ou que violem direitos de terceiros;</li>
  <li>Extrair, copiar ou exportar dados em volume incompatível com as atribuições do usuário;</li>
  <li>Utilizar ferramentas automatizadas (bots, scripts) para acesso ou extração de dados sem autorização;</li>
  <li>Praticar qualquer ato que comprometa a integridade, disponibilidade ou confidencialidade do sistema.</li>
</ul>
<hr>
<h2>13. Legislação Aplicável e Foro</h2>
<p>13.1. Este documento é regido pelas leis da República Federativa do Brasil, especialmente:</p>
<ul>
  <li>Lei Geral de Proteção de Dados Pessoais — LGPD (Lei nº 13.709/2018);</li>
  <li>Marco Civil da Internet (Lei nº 12.965/2014) e seu Decreto regulamentador (nº 8.771/2016);</li>
  <li>Código Civil Brasileiro (Lei nº 10.406/2002);</li>
  <li>Código de Defesa do Consumidor, quando aplicável (Lei nº 8.078/1990).</li>
</ul>
<p>13.2. Para dirimir quaisquer controvérsias oriundas deste documento, fica eleito o foro da comarca da sede de <strong>{{company_name}}</strong>, com renúncia expressa a qualquer outro, por mais privilegiado que seja.</p>
<hr>
<h2>14. Atualizações destes Termos</h2>
<p>14.1. Este documento pode ser revisado e atualizado periodicamente para refletir mudanças na legislação, nas funcionalidades do sistema ou nas práticas de tratamento de dados.</p>
<p>14.2. Toda nova versão dos termos será comunicada ao usuário no próximo acesso ao sistema, sendo exigida nova manifestação de aceite antes da continuidade do uso.</p>
<p>14.3. O aceite de versão anterior não implica aceitação automática de versões subsequentes.</p>
<p>14.4. O histórico de versões aceitas por cada usuário é mantido pelo sistema para fins de auditoria e conformidade.</p>
<hr>
<h2>15. Consentimento e Aceite</h2>
<p>Ao marcar a caixa de aceite e prosseguir com o uso do sistema, o usuário declara expressamente que:</p>
<ul>
  <li>Leu e compreendeu integralmente este documento;</li>
  <li>Concorda com todos os termos e condições aqui estabelecidos;</li>
  <li>Consente com o tratamento de seus dados pessoais para as finalidades descritas neste documento;</li>
  <li>Tem capacidade civil plena para manifestar este consentimento;</li>
  <li>Tem ciência de que este aceite é registrado com data, hora, endereço IP e versão do documento aceita, nos termos do art. 8º da LGPD.</li>
</ul>
<p><em>Este documento foi elaborado em conformidade com a Lei nº 13.709/2018 (LGPD), a Lei nº 12.965/2014 (Marco Civil da Internet) e as diretrizes da Autoridade Nacional de Proteção de Dados (ANPD).</em></p>
HTML;
    }
}
