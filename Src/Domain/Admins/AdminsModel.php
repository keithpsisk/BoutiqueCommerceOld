<?php
declare(strict_types=1);

namespace It_All\BoutiqueCommerce\Src\Domain\Admins;

use It_All\BoutiqueCommerce\Src\Infrastructure\Model;
use It_All\BoutiqueCommerce\Src\Infrastructure\UserInterface\FormHelper;
use It_All\BoutiqueCommerce\Src\Infrastructure\Utilities\ValidationService;
use It_All\BoutiqueCommerce\Src\Infrastructure\Database\Queries\QueryBuilder;
use Psr\Log\InvalidArgumentException;

class AdminsModel extends Model
{
    private $rolesSelectFieldOptions;

    public function __construct()
    {
        // Set select field options
        $this->rolesSelectFieldOptions = [
            '-- select --' => 'disabled',
            'owner' => 'owner',
            'director' => 'director',
            'manager' => 'manager',
            'shipper' => 'shipper',
            'admin' => 'admin',
            'store' => 'store',
            'bookkeeper' => 'bookkeeper'
        ];
        parent::__construct('admins');
    }

    protected function setColumns()
    {
        $this->columns = [

            'username' => [
                'tag' => 'input',
                'label' => 'Username',
                'validation' => [
                    'required' => null,
                    '%^[a-zA-Z]+$%' => 'only letters',
                    'minlength' => 5,
                    'maxlength' => 20
                ],
                'attributes' => [
                    'id' => 'username',
                    'name' => 'username',
                    'type' => 'text',
                    'size' => '15',
                    'maxlength' => '20',
                    'value' => ''
                ]
            ],

            'name' => [
                'tag' => 'input',
                'label' => 'Name',
                'validation' => [
                    'required' => null,
                    'alphaspace' => null,
                    'maxlength' => 50
                ],
                'attributes' => [
                    'id' => 'name',
                    'name' => 'name',
                    'type' => 'text',
                    'size' => '15',
                    'maxlength' => '50',
                    'value' => ''
                ]
            ],

            'role' => [
                'tag' => 'select',
                'label' => 'Role',
                'validation' => ['required' => null],
                'attributes' => [
                    'id' => 'role',
                    'name' => 'role',
                    'type' => 'select',
                    'value' => ''
                ],
                'options' => $this->rolesSelectFieldOptions,
                'selected' => 'disabled'
            ],

            'password_hash' => [
                'tag' => 'input',
                'label' => 'Password',
                'validation' => ['minlength' => 12],
                'attributes' => [
                    'id' => 'password',
                    'type' => 'password',
                    'name' => 'password_hash',
                    'size' => '20',
                    'maxlength' => '30',
                ],
            ]
        ];
    }

    public function getFormFields(string $formType = 'insert', bool $persistPasswords = false): array
    {
        if ($formType != 'insert' && $formType != 'update') {
            throw new InvalidArgumentException("formType must be insert or update ".$formType);
        }

        $fields = array_merge($this->columns, [

            'confirm_password_hash' => [
                'tag' => 'input',
                'label' => 'Confirm Password',
                'validation' => ['minlength' => 12, 'confirm' => null],
                'attributes' => [
                    'type' => 'password',
                    'name' => 'confirm_password_hash',
                    'size' => '20',
                    'maxlength' => '30',
                ],
                'persist' => $persistPasswords,
            ],

            'submit' => FormHelper::getSubmitField()
        ]);

        $fields['password_hash']['persist'] = $persistPasswords;

        if ($formType == 'insert') {
            $fields['password_hash']['validation']['required'] = null;
            $fields['confirm_password_hash']['validation']['required'] = null;
        } else { // update
            $fields['password_hash']['label'] = 'Change Password (leave blank to keep existing password)';
            $fields['confirm_password_hash']['label'] = 'Confirm New Password';
            // override post method
            $fields['_METHOD'] = [
                'tag' => 'input',
                'attributes' => [
                    'type' => 'hidden',
                    'name' => '_METHOD',
                    'value' => 'PUT'
                ]
            ];
        }

        return $fields;
    }

    private function validateRole(string $roleCheck): bool
    {
        return in_array($roleCheck, $this->roles);
    }

    public function getValidationRules($formType = 'insert'): array
    {
        if ($formType != 'insert' && $formType != 'update') {
            throw new InvalidArgumentException("formType must be insert or update ".$formType);
        }

        return ValidationService::getRules($this->getFormFields($formType));
    }

//    If a null password is passed, the password field is not updated
    public function update(int $id, string $name, string $username, string $role, string $password = null)
    {
        if (!$this->validateRole($role)) {
            throw new \Exception("Admin being updated with invalid role $role");
        }
        $q = new QueryBuilder("UPDATE admins SET name = $1, username = $2, role = $3", $name, $username, $role);
        $argNum = 4;
        if ($password !== null) {
            $q->add(", password_hash = $$argNum", password_hash($password, PASSWORD_DEFAULT));
            $argNum++;
        }
        $q->add(" WHERE id = $$argNum RETURNING username", $id);
        return $q->execute();
    }

    public function delete(int $id)
    {
        return $this->deleteByPrimaryKey($id, 'id', 'username');
    }

    public function checkRecordExistsForUsername(string $username): bool
    {
        $q = new QueryBuilder("SELECT id FROM admins WHERE username = $1", $username);
        $res = $q->execute();
        return pg_num_rows($res) > 0;
    }

    public function selectForUsername(string $username)
    {
        $q = new QueryBuilder("SELECT * FROM admins WHERE username = $1", $username);
        return $q->execute();
    }

    // If a null password is passed, the password field is not checked
    public function recordChanged(int $id, string $name, string $username, string $role, string $password = null): bool
    {
        if (!$record = $this->selectForPrimaryKey($id)) {
            throw new \Exception("No admins record for id $id");
        }

        if ($name != $record['name'] || $username != $record['username'] || $role != $record['role']) {
            return true;
        }
        if ($password !== null && password_hash($password, PASSWORD_DEFAULT) != $record['password']) {
            return true;
        }

        return false;
    }
}
