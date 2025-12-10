<?php
namespace classes\auth;

use classes\Methods;
use classes\user\UserHandler;
use classes\utility\Crud;
use Database\model\AuthLocal;

class LocalAuthentication extends Crud {
    private const ID_COLUMNS = ["username","email", "phone"];
    private const KEY_COLUMN = "password";
    private ?string $redirectUri = null;
    private bool $guest = false;
    private ?array $error = null;
    private ?object $user = null;
    private ?string $authId = null;
    private ?string $authKey = null;
    private bool $userisEnabled = true;


    /**
     * Please call validate() and then login().
     * Retrieve error with getError().
     */


    function __construct() {
        parent::__construct(AuthLocal::newStatic(), "authloc");
    }


    public function login(): void {
        if(!empty($this->error)) return;
        $user = toArray($this->user);
        $keys = array_keys($user);
        $keys[] = "logged_in";
        $keys[] = "localAuth";
        setSessions($user,$keys);
    }
    public function getUser(): ?object { return $this->user; }
    public function getError(): ?array { return $this->error; }
    public function validate(array $params): bool {
        $this->set($params);
        if(!empty($this->error)) return false;
        if(!$this->findUser()) {
            $this->error = Response()->arrayError(
                "Ingen tilgængelig matchende bruger fundet. Prøv igen.",
                [],
                401
            );
            return false;
        }

        if(!$this->userisEnabled) {
            $this->error = Response()->arrayError(
                "Din konto er ikke længere aktiv. Du burde have modtaget en email med yderligere detaljer.",
                [],
                403
            );
            return false;
        }

        return true;
    }


    private function set(array $params): void {
        foreach (self::ID_COLUMNS as $column) {
            if(array_key_exists($column, $params) && !empty($params[$column])) {
                $this->authId = $params[$column];
                break;
            }
        }
        if(array_key_exists(self::KEY_COLUMN, $params) && !empty($params[self::KEY_COLUMN])) {
            $this->authKey = $params[self::KEY_COLUMN];
        }
        if(is_null($this->authId)) $this->error = Response()->arrayError("Venligst angiv et brugernavn, email eller telefonnummer.");
        elseif(is_null($this->authKey)) $this->error = Response()->arrayError("Venligst angiv et kodeord.");
    }

    private function findUser(): bool {
        $password = passwordHashing($this->authKey);
        for($i = 0; $i < 2; $i++) {
            $query = $this->queryBuilder();
            $query->startGroup("OR");
                foreach (self::ID_COLUMNS as $column) {
                    $query->where($column, $this->authId);
                }
            $query->endGroup();
            $query->where(self::KEY_COLUMN, $password);
            $query->where("enabled", (int)($i === 1));
            $auth = $this->queryGetFirst($query);
            if(!isEmpty($auth) && !isEmpty($auth?->user)) {
                $this->user = $auth->user;
                $this->userisEnabled = $auth->enabled === 1;
                return true;
            }
        }
        return false;
    }




}