<?php
/**
 * User repository
 */

namespace Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

/**
 * Class UserRepository.
 */
class UserRepository
{
    /**
     * Doctrine DBAL connection.
     *
     * @var \Doctrine\DBAL\Connection $db
     */
    protected $db;

    /**
     * TagRepository constructor.
     *
     * @param \Doctrine\DBAL\Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * Gets user data by login.
     *
     * @param string $id User id
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array Result
     */
    public function getUserById($id)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('u.PK_idUsers = :id')
            ->setParameter(':id', $id, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetch();

        return !$result ? [] : $result;
    }
    /**
     * Loads user by login.
     *
     * @param string $login User login
     * @throws UsernameNotFoundException
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array Result
     */
    public function loadUserByLogin($email)
    {
        try {
            $user = $this->getUserByLogin($email);

            if (!$user || !count($user)) {
                throw new UsernameNotFoundException(
                    sprintf('Username "%s" does not exist.', $email)
                );
            }

            $roles = $this->getUserRoles($user['PK_idUsers']);

            if (!$roles || !count($roles)) {
                throw new UsernameNotFoundException(
                    sprintf('Username "%s" does not exist.', $email)
                );
            }

            return [
                'email' => $user['email'],
                'password' => $user['password'],
                'roles' => $roles,
            ];
        } catch (DBALException $exception) {
            throw new UsernameNotFoundException(
                sprintf('Username "%s" does not exist.', $email)
            );
        } catch (UsernameNotFoundException $exception) {
            throw $exception;
        }
    }


    /**
     * Gets user data by login.
     *
     * @param string $login User login
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array Result
     */
    public function getUserByLogin($email)
    {
        try {
            $queryBuilder = $this->db->createQueryBuilder();
            $queryBuilder->select('u.PK_idUsers', 'u.email', 'u.password')
                ->from('users', 'u')
                ->where('u.email = :email')
                ->setParameter(':email', $email, \PDO::PARAM_STR);

            return $queryBuilder->execute()->fetch();
        } catch (DBALException $exception) {
            return [];
        }
    }


    /**
     * Gets user roles by User ID.
     *
     * @param integer $userId User ID
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array Result
     */
    public function getUserRoles($userId)
    {
        $roles = [];

        try {
            $queryBuilder = $this->db->createQueryBuilder();
            $queryBuilder->select('r.name')
                ->from('users', 'u')
                ->innerJoin('u', 'roles', 'r', 'u.role_id = r.id')
                ->where('u.PK_idUsers = :id')
                ->setParameter(':id', $userId, \PDO::PARAM_INT);
            $result = $queryBuilder->execute()->fetchAll();

            if ($result) {
                $roles = array_column($result, 'name');
            }

            return $roles;
        } catch (DBALException $exception) {
            return $roles;
        }
    }


    protected function queryAll()
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select(
            'u.PK_idUsers',
            'u.name',
            'u.surname',
            'u.email',
            'u.idPicture',
            'u.access',
            'u.birthDate')
            ->from('users', 'u');
    }

}