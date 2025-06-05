<?php
function validateEmail($email)
{
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new Exception('Neispravna email adresa');
  }
  if (strlen($email) > 255) {
    throw new Exception('Email adresa je predugačka');
  }
}

function validatePassword($password)
{
  if (strlen($password) < 8) {
    throw new Exception('Lozinka mora imati najmanje 8 karaktera');
  }
  if (!preg_match('/[A-Z]/', $password)) {
    throw new Exception('Lozinka mora sadržati najmanje jedno veliko slovo');
  }
  if (!preg_match('/[a-z]/', $password)) {
    throw new Exception('Lozinka mora sadržati najmanje jedno malo slovo');
  }
  if (!preg_match('/[0-9]/', $password)) {
    throw new Exception('Lozinka mora sadržati najmanje jedan broj');
  }
}

function validateUsername($username)
{
  if (strlen($username) < 3 || strlen($username) > 50) {
    throw new Exception('Korisničko ime mora biti između 3 i 50 karaktera');
  }
  if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    throw new Exception('Korisničko ime može sadržati samo slova, brojeve i donju crtu');
  }
}
?>