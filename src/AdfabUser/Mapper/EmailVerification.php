<?php
namespace AdfabUser\Mapper;

use Doctrine\ORM\EntityManager;
use AdfabUser\Options\ModuleOptions;
use AdfabUser\Entity\EmailVerification as Model;
use Zend\Stdlib\Hydrator\HydratorInterface;
use ZfcBase\EventManager\EventProvider;

class EmailVerification extends EventProvider
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var \AdfabUser\Options\ModuleOptions
     */
    protected $options;

    public function __construct(EntityManager $em, ModuleOptions $options)
    {
        $this->em      = $em;
        $this->options = $options;
    }

    protected function getEntityRepository()
    {
        return $this->em->getRepository('AdfabUser\Entity\EmailVerification');
    }

    public function findByEmail($email)
    {
        $entity = $this->getEntityRepository()->findOneBy(array('email_address' => $email));
        $this->getEventManager()->trigger('find', $this, array('entity' => $entity));

        return $entity;
    }

    public function findByRequestKey($key)
    {
        $entity = $this->getEntityRepository()->findOneBy(array('request_key' => $key));
        $this->getEventManager()->trigger('find', $this, array('entity' => $entity));

        return $entity;
    }

    public function cleanExpiredVerificationRequests($expiryTime=86400)
    {
        $now = new \DateTime((int) $expiryTime . ' seconds ago');
        $dql = "DELETE AdfabUser\Entity\EmailVerification ev WHERE ev.request_time <= '" . $now->format('Y-m-d H:i:s') . "'";

        return $this->em->createQuery($dql)->getResult();
    }

    public function insert($entity, $tableName = null, HydratorInterface $hydrator = null)
    {
        $this->em->persist($entity);
        $this->em->flush();

        return $entity;
    }

    public function remove(Model $evrModel)
    {
        $this->em->remove($evrModel);
        $this->em->flush();

        return true;
    }
}
