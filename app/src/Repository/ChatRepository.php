<?php
/**
 * Chat repository.
 */
namespace Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Utils\Paginator;

/**
 * Class ChayRepository.
 */
class ChatRepository
{
    /**
     * Number of items per page.
     *
     * const int NUM_ITEMS
     */
    const NUM_ITEMS = 3;

    /**
     * Doctrine DBAL connection.
     *
     * @var \Doctrine\DBAL\Connection $db
     */
    protected $db;

    /**
     * PostsRepository constructor.
     *
     * @param \Doctrine\DBAL\Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }
    /**
     * Fetch all records.
     *
     * @return array Result
     */
    public function findAll()
    {
        $queryBuilder = $this->queryAll();

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * Get records paginated.
     *
     * @param int $page Current page number
     *
     * @return array Result
     */
    public function findAllPaginated($page, $userId, $id)
    {
        $countQueryBuilder = $this->queryAll($page)
            ->select('COUNT(DISTINCT m.PK_time) AS total_results')
            ->where('m.FK_idConversations = 1')
            ->setMaxResults(3);

        $queryBuilder = $this->db->createQueryBuilder();
        $result =
            $queryBuilder->select('m.PK_time', 'm.content', 'u.name', 'u.surname')
            ->from('messages', 'm')
            ->innerJoin('m', 'participants', 'p', 'p.FK_idConversations = m.FK_idConversations')
            ->innerJoin('m', 'users', 'u', 'u.PK_idUsers = m.FK_idUsers')
            ->where('p.FK_idUsers = :userId',
                              'm.FK_idConversations = :id')
            ->orderBy('m.PK_time', 'DESC')
            ->setParameters(array(':userId'=> $userId, ':id' => $id));


        $paginator = new Paginator($result, $countQueryBuilder);
        $paginator->setCurrentPage($page);
        $paginator->setMaxPerPage(static::NUM_ITEMS);

        return $paginator->getCurrentPageResults();
    }


    /**
     * Save record.
     *
     * @param array $post Post
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function save($message, $userId)
    {
        $this->db->beginTransaction();

        try {
            $currentDateTime = new \DateTime();
            unset($message['messages']);

                // add new record
                $message['PK_time'] = $currentDateTime->format('Y-m-d H:i:s');
                $message['FK_idUsers'] = $userId ;
                $message['FK_idConversations'] = 3 ;
                $this->db->insert('messages', $message);

            $this->db->commit();
        } catch (DBALException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

//    /**
//     * Remove record.
//     *
//     * @param array $post Post
//     *
//     * @throws \Doctrine\DBAL\DBALException
//     *
//     * @return boolean Result
//     */
//    public function delete($post)
//    {
//        $this->db->beginTransaction();
//
//        try {
//            $this->removeLinkedTags($post['id']);
//            $this->db->delete('posts', ['id' => $post['id']]);
//            $this->db->commit();
//        } catch (DBALException $e) {
//            $this->db->rollBack();
//            throw $e;
//        }
//    }

    /**
     * Query all records.
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder Result
     */
    protected function queryAll()
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select(
            'm.PK_time',
            'm.FK_idConversations',
            'm.FK_idUsers',
            'm.content'
        )->from('messages', 'm');
    }
}
