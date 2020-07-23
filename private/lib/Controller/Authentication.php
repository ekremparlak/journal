<?php declare(strict_types=1);

namespace App\Controller;

use App\Exception\UserException;
use App\Service\AuthenticationService;
use App\Utility\Notification;
use App\Utility\Redirect;
use App\Validator\AuthenticationValidator;

class Authentication extends AbstractController
{
    // Route url constants, to keep paths consistent within multiple classes
    public const LOGIN_URL         = BASE_URL . '/auth/login';
    public const LOGIN_POST_URL    = BASE_URL . '/auth/login/post';
    public const REGISTER_URL      = BASE_URL . '/auth/register';
    public const REGISTER_POST_URL = BASE_URL . '/auth/register/post';
    public const LOGOUT_URL        = BASE_URL . '/auth/logout';

    private AuthenticationService $service;
    private AuthenticationValidator $validator;

    public function __construct(array $routeParameters)
    {
        parent::__construct($routeParameters);

        $this->service   = new AuthenticationService();
        $this->validator = new AuthenticationValidator($_POST);
    }

    /**
     * Action for post request when registration is submitted in /auth/register
     *
     * @return void
     */
    public function register(): void
    {
        // Registration is not open, administrators can only create user accounts
        //$this->ensureUserHasAdminRights();

        try {
            /** @see AuthenticationValidator::register() */
            $this->validator->validate(__FUNCTION__);

            // Register the user
            $this->service->register(
                $_POST['username'],
                $_POST['password'],
                $_POST['email']
            );

            // Present success message
            $this->setNotification(
                Notification::TYPE_SUCCESS,
                'Registration successful'
            );
        } catch (UserException $e) {
            $this->setNotification(
                Notification::TYPE_ERROR,
                $e->getMessage()
            );
        }

        $this->registerView();
    }

    public function registerView(): void
    {
        $this->template->render('authenticate/register');
    }

    /**
     * Action for post request when login details are submitted on /auth/login
     *
     * @return void
     */
    public function login(): void
    {
        $this->ensureUserIsNotLoggedIn();

        try {
            /** @see AuthenticationValidator::login() */
            $this->validator->validate(__FUNCTION__);

            // Log the user in
            $this->service->login($_POST['username'], $_POST['password']);

            Redirect::to(Welcome::DASHBOARD_URL);
        } catch (UserException $e) {
            $this->setNotification(
                Notification::TYPE_ERROR,
                $e->getMessage()
            );
        }

        $this->loginView();
    }

    public function loginView(): void
    {
        $this->ensureUserIsNotLoggedIn();

        $this->template->render('authenticate/login');
    }

    /**
     * Action for logging out a user, triggered when visiting /auth/logout
     *
     * @return void
     */
    public function logout(): void
    {
        $this->service->logout();

        $this->setNotification(Notification::TYPE_INFO, 'You have been logged out');
        Redirect::to(self::LOGIN_URL);
    }
}