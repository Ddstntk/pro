<?php
/**
 * Posts repository.
 */
namespace Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Utils\Paginator;

/**
 * Class PostsRepository.
 */
class PostsRepository
{
    /**
     * Number of items per page.
     *
     * const int NUM_ITEMS
     */
    const NUM_ITEMS = 10;

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
    public function findAllPaginated($page = 1)
    {
        $countQueryBuilder = $this->queryAll($page = 1)
            ->select('COUNT(DISTINCT p.PK_idPosts) AS total_results')
            ->setMaxResults(4);

        $paginator = new Paginator($this->queryAll(), $countQueryBuilder);
        $paginator->setCurrentPage($page);
        $paginator->setMaxPerPage(self::NUM_ITEMS);

        return $paginator->getCurrentPageResults();
    }


    /**
     * Save record.
     *
     * @param array $post Post
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function save($post)
    {
        $this->db->beginTransaction();

        try {
            $currentDateTime = new \DateTime();
            $post['modified_at'] = $currentDateTime->format('Y-m-d H:i:s');
            unset($post['posts']);

            if (isset($post['id']) && ctype_digit((string) $post['id'])) {
                // update record
                $postId = $post['id'];
                unset($post['id']);
                $this->db->update('posts', $post, ['id' => $postId]);
            } else {
                // add new record
                $post['created_at'] = $currentDateTime->format('Y-m-d H:i:s');
                $post['FK_idUsers'] = 1;
                $this->db->insert('posts', $post);
            }
            $this->db->commit();
        } catch (DBALException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Remove record.
     *
     * @param array $post Post
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return boolean Result
     */
    public function delete($post)
    {
        $this->db->beginTransaction();

        try {
            $this->removeLinkedTags($post['id']);
            $this->db->delete('posts', ['id' => $post['id']]);
            $this->db->commit();
        } catch (DBALException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Query all records.
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder Result
     */
    protected function queryAll()
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select(
            'p.PK_idPosts',
            'p.FK_idUsers',
            'p.content',
            'p.idMedia'
        )->from('posts', 'p');
    }
}
