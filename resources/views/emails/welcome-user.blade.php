<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bem-vindo ao Sistema</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .credentials {
            background: white;
            border: 2px solid #667eea;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .credentials-title {
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        .credential-item {
            margin: 8px 0;
        }
        .credential-label {
            font-weight: bold;
            color: #555;
        }
        .credential-value {
            background: #f0f0f0;
            padding: 8px 12px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 14px;
            display: inline-block;
        }
        .password {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 8px 12px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 16px;
            font-weight: bold;
            color: #856404;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning-title {
            font-weight: bold;
            color: #856404;
            margin-bottom: 5px;
        }
        .button {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            color: #888;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Bem-vindo ao Sistema!</h1>
    </div>
    
    <div class="content">
        <p>Olá <strong>{{ $user->name }}</strong>,</p>
        
        <p>É com grande satisfação que informamos que você foi incluído no nosso sistema. Segue abaixo suas credenciais de acesso:</p>
        
        <div class="credentials">
            <div class="credentials-title">📋 Credenciais de Acesso</div>
            
            <div class="credential-item">
                <span class="credential-label">E-mail:</span><br>
                <span class="credential-value">{{ $user->email }}</span>
            </div>
            
            <div class="credential-item" style="margin-top: 15px;">
                <span class="credential-label">Senha Temporária:</span><br>
                <span class="password">{{ $temporaryPassword }}</span>
            </div>
        </div>
        
        <div class="warning">
            <div class="warning-title">⚠️ Importante</div>
            <p style="margin: 0;">Por ser seu primeiro acesso, você será <strong>obrigado a alterar sua senha</strong> após fazer login. Isso é uma medida de segurança para proteger sua conta.</p>
        </div>
        
        <p>Para acessar o sistema, clique no botão abaixo:</p>
        
        <div style="text-align: center;">
            <a href="{{ config('app.url') }}" class="button">Acessar Sistema</a>
        </div>
        
        <p style="margin-top: 30px;">
            Se tiver qualquer dúvida ou precisar de ajuda, não hesite em entrar em contato com o administrador do sistema.
        </p>
        
        <p>Atenciosamente,<br>
        <strong>Equipe do Sistema</strong></p>
    </div>
    
    <div class="footer">
        <p>Este é um e-mail automático, por favor não responda esta mensagem.</p>
    </div>
</body>
</html>
