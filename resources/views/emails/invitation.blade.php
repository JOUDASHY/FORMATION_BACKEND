<!DOCTYPE html>
<html>
<head>
    <title>Set Up Your Account</title>
</head>
<body>
    <h1>Salut, {{ $user->name }}</h1>
    <p>Vous avez été invité à créer votre compte. Cliquez sur le lien ci-dessous pour 
    définir votre mot de passe:</p>
    <a href="{{ $url }}">Définir mon mot de passe</a>
</body>
</html>
