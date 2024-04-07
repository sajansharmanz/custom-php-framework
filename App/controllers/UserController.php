<?php

namespace App\Controllers;

use Framework\Database;
use Framework\Validation;
use Framework\Session;

class UserController
{
    protected $db;

    public function __construct()
    {
        $config = require basePath('config/db.php');
        $this->db = new Database($config);
    }

    public function login()
    {
        loadView('users/login');
    }

    public function create()
    {
        loadView('users/create');
    }

    public function store()
    {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $city = $_POST['city'];
        $state = $_POST['state'];
        $password = $_POST['password'];
        $passwordConfirmation = $_POST['password_confirmation'];

        $errors = [];

        if (!Validation::email($email)) {
            $errors['email'] = "Please enter a valid email address";
        }

        if (!Validation::string($name, 2, 50)) {
            $errors['name'] = "Name must be between 2 and 50 characters";
        }

        if (!Validation::string($password, 6, 50)) {
            $errors['password'] = "Password must be at least 6 characters";
        }

        if (!Validation::match($password, $passwordConfirmation)) {
            $errors['password'] = "Passwords do not match";
        }

        if (!empty($errors)) {
            loadView('users/create', [
                'errors' => $errors,
                'user' => [
                    'name' => $name,
                    'email' => $email,
                    'city' => $city,
                    'state' => $state
                ]
            ]);
            exit;
        }

        $params = [
            'email' => $email
        ];

        $user = $this->db->query("SELECT * FROM users WHERE email = :email", $params)->fetch();

        if ($user) {
            $errors['email'] = "Email already exists";

            loadView('users/create', [
                'errors' => $errors,
            ]);
            exit;
        }

        $params = [
            'name' => $name,
            'email' => $email,
            'city' => $city,
            'state' => $state,
            'password' => password_hash($password, PASSWORD_ARGON2ID)
        ];

        $this->db->query("INSERT INTO users (name, email, city, state, password) VALUES (:name, :email, :city, :state, :password)", $params);

        // Get new user id
        $userId = $this->db->conn->lastInsertId();

        Session::set('user', [
            'id' => $userId,
            'name' => $name,
            'email' => $email,
            'city' => $city,
            'state' => $state,
        ]);

        redirect("/");
    }

    public function logout()
    {
        Session::clearAll();

        $params = session_get_cookie_params();
        setcookie('PHPSESSID', '', time() - 86400, $params['path'], $params['domain']);

        redirect("/");
    }

    public function authenticate()
    {
        $email = $_POST['email'];
        $password = $_POST['password'];

        $errors = [];

        if (!Validation::email($email)) {
            $errors['email'] = "Please enter a valid email";
        }

        if (!Validation::string($password, 6, 50)) {
            $errors['password'] = "Password must be at least 6 characters";
        }

        if (!empty($errors)) {
            loadView('users/login', [
                'errors' => $errors
            ]);
            exit;
        }

        $params = [
            'email' => $email,
        ];

        $user = $this->db->query("SELECT * FROM users WHERE email = :email", $params)->fetch();

        if (!$user) {
            $errors['email'] = "Incorrect username and/or password";

            loadView('users/login', [
                'errors' => $errors
            ]);
            exit;
        }

        if (!password_verify($password, $user->password)) {
            $errors['email'] = "Incorrect username and/or password";

            loadView('users/login', [
                'errors' => $errors
            ]);
            exit;
        }

        Session::set('user', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'city' => $user->city,
            'state' => $user->state,
        ]);

        redirect('/');
    }
}
