@component('mail::message')
# Acesso detectado em outro dispositivo

Olá, **{{ $user->name }}**.

Detectamos um novo acesso à sua conta. Por isso, **sua sessão anterior foi encerrada automaticamente**.

@component('mail::panel')
**IP do novo acesso:** {{ $ip }}
**Data e hora:** {{ $loginAt }}
@endcomponent

Se foi você quem fez este acesso, pode ignorar este email.

Caso **não reconheça este acesso**, recomendamos:

@component('mail::button', ['url' => config('app.url') . '/login', 'color' => 'red'])
Acessar minha conta agora
@endcomponent

Se necessário, entre em contato com o suporte imediatamente.

Atenciosamente,
**{{ config('app.name') }}**
@endcomponent