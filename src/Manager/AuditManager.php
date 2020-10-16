<?php

namespace App\Manager;

use App\Entity\ServiceManagement\Audit;
use App\Entity\UserManagement\User;
use App\Persistence\ObjectManager;
use App\Serializer\FormationManagement\CourseSerializer;
use App\Utils\Utils;
use Doctrine\Common\Util\ClassUtils;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * AuditManager
 *
 * @author null
 */
class AuditManager
{
    /**
     * @var EntityManager
     */
    protected $em;

    protected $logger;
    protected $sfSession;
    protected $translator;
    protected $serializer;

    /**
     * UserModuleFollowManager constructor.
     * @param EntityManager $em
     * @param Logger $logger
     */
    public function __construct(ObjectManager $em, LoggerInterface $logger, SessionInterface $sfSession, ContainerInterface $container, SerializerInterface $serializer)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->sfSession = $sfSession;
        $this->container = $container;
    }

    public function generateAuditEntity($old = null, $new = null, $action, User $user)
    {
        if ($old || $new) {
            if ($new) {
                $entity = $new;
            } else {
                $entity = $old;
            }

            if ($old) {
                switch (Utils::mbBasename(get_class($entity))) {
                    case 'Course':
                        $courseSerializer = new CourseSerializer();
                        $old_entity = $courseSerializer->serialize($old);
                        break;
                    default:
                        $old_entity = null;
                        break;
                }
            } else {
                $old_entity = null;
            }

            if ($new) {
                switch (Utils::mbBasename(get_class($entity))) {
                    case 'Course':
                        $courseSerializer = new CourseSerializer();
                        $new_entity = $courseSerializer->serialize($new);
                        break;
                    default:
                        $old_entity = null;
                        break;
                }
            } else {
                $new_entity = null;
            }

            //renseigner l'audit trail
            $audit = new Audit();
            $audit->setAction($action);
            $audit->setEntityName(Utils::mbBasename(get_class($entity)));
            $audit->setEntityId($entity->getId());
            $audit->setOldEntity($old_entity);
            $audit->setCurentEntity($new_entity);
            $audit->setDateTime(new \DateTime());
            $audit->setUser($user);

            try {
                $this->em->persist($audit);
                $this->em->flush();

                return true;
            } catch (\Doctrine\DBAL\DBALException $exception) {
                $this->sfSession->getFlashBag()->add('error', $exception->getMessage());
            }
        }

        return;
    }

    public function generateAudit($oldEntity = null, $newEntity = null, $action, User $user)
    {
        if (!($oldEntity == null && $newEntity == null)) {
            $newContent = $oldContent = '';
            if ($newEntity != null) {
                $entity = $newEntity;
            } else {
                $entity = $oldEntity;
            }
            if ($oldEntity != null) {
                $oldContent = $this->inalienableContent($oldEntity, false);
            }
            if ($newEntity != null) {
                $newContent = $this->inalienableContent($newEntity, true);
            }

            //renseigner l'audit trail
            $audit = new Audit();
            $audit->setAction($action);
            $audit->setEntityName(Utils::mbBasename(get_class($entity)));
            $audit->setEntityId($entity->getId());
            $audit->setOldValue(json_encode($oldContent));
            $audit->setCurentValue(json_encode($newContent));
            $audit->setDateTime(new \DateTime());
            $audit->setUser($user);
            try {
                $this->em->persist($audit);
                $this->em->flush();

                return true;
            } catch (\Doctrine\DBAL\DBALException $exception) {
                $this->sfSession->getFlashBag()->add('error', $exception->getMessage());
            }
        }

        return false;
    }

    private function inalienableContent($entity, $isNew)
    {
        $content = ['Audit' => get_class($entity)];
        $lgetter = array_filter(get_class_methods($entity), function ($method) {
            return 'get' === substr($method, 0, 3);
        });
        $Unfolowfunction = ['getIterator', 'getFile', 'getFileAudio', 'getImage', 'getCurrentChronoSeconds'];

        foreach ($lgetter as $function) {
            if (!in_array($function, $Unfolowfunction)) {
                $attribut = call_user_func_array([$entity, $function], []);
                $attrTitle = strtoupper(substr($function, 3));
                //type de valeurs particulières
                $isDate = is_a($attribut, 'DateTime');
                $isArray = is_array($attribut);
                $isUser = is_a($attribut, 'App\Entity\UserManagement\User');
                $isMetadata = is_a($attribut, 'Doctrine\ORM\Mapping\ClassMetadata');
                $isCollection = is_a($attribut, 'Doctrine\ORM\PersistentCollection');
                $isString = is_string('attribut');

                //si c'est un objet on récupère les valeurs principale (hors champs trait) sinon on l'enregistre tel quel
                if ($isDate) {
                    $content[$attrTitle] = $attribut->format('d/m/Y H-i-s');
                } elseif ($isUser) {
                    $content[$attrTitle] = ' => id :' . $attribut->getId() . ' & username :' . $attribut->getUsername();
                } elseif ($this->isEntity($attribut)) {
                    //$content[$attrTitle] = $attribut->getId();
                    $lSubgetter = array_filter(get_class_methods($attribut), function ($method) {
                        return 'get' === substr($method, 0, 3);
                    });
                    $subContent = '';
                    foreach ($lSubgetter as $subFunction) {
                        if (!in_array($subFunction, $Unfolowfunction)) {
                            $subAtribut = call_user_func_array([$attribut, $subFunction], []);
                            //type de valeurs particulières
                            $isSubDoctrineEntity = $this->isEntity($subAtribut);
                            $isSubDate = is_a($subAtribut, 'DateTime');
                            $isSubArray = is_array($subAtribut);
                            $isSubUser = is_a($subAtribut, 'App\Entity\UserManagement\User');
                            $isSubMetadata = is_a($subAtribut, 'Doctrine\ORM\Mapping\ClassMetadata');
                            $isSubCollection = is_a($subAtribut, 'Doctrine\ORM\PersistentCollection');
                            //si c'est une entité on ne récupère que son id
                            if (!$isSubUser && !$isSubArray && !$isSubCollection && $subFunction != 'getIsValid' && $subFunction != 'getRevision' && !$isSubDate && !$isSubMetadata) {
                                if ($isSubDoctrineEntity) {
                                    $subContent .= strtoupper(substr($subFunction, 3)) . 'id : ' . $subAtribut->getId() . ' | ';
                                } else {
                                    $subContent .= strtoupper(substr($subFunction, 3)) . ' :' . $subAtribut . ' | ';
                                }
                            }
                        }
                    }
                    $content[$attrTitle] = $subContent;
                } elseif ($isCollection) {
                    if ($isNew) {
                        $arrayId = '';
                        foreach ($attribut as $value) {
                            if ($arrayId == '') {
                                $arrayId .= 'Entity: ' . get_class($value) . ' => |-| ';
                            }
                            $lSubgetter = array_filter(get_class_methods($value), function ($method) {
                                return 'get' === substr($method, 0, 3);
                            });
                            foreach ($lSubgetter as $subFunction) {
                                if (!in_array($subFunction, $Unfolowfunction)) {
                                    if ($value != null) {
                                        $subAtribut = call_user_func_array([$value, $subFunction], []);
                                        //type de valeurs particulières
                                        $isSubDoctrineEntity = $this->isEntity($subAtribut);
                                        $isSubDate = is_a($subAtribut, 'DateTime');
                                        $isSubArray = is_array($subAtribut);
                                        $isSubUser = is_a($subAtribut, 'App\Entity\UserManagement\User');
                                        $isSubMetadata = is_a($subAtribut, 'Doctrine\ORM\Mapping\ClassMetadata');
                                        $isSubCollection = is_a($subAtribut, 'Doctrine\ORM\PersistentCollection');
                                        //si c'est une entité on ne récupère que son id
                                        if (!$isSubUser && !$isSubArray && !$isSubCollection && $subFunction != 'getIsValid' && $subFunction != 'getRevision' && !$isSubMetadata) {
                                            if ($isSubDate) {
                                                $arrayId .= strtoupper(substr($subFunction, 3)) . $subAtribut->format('d/m/Y H-i-s') . '& ';
                                            } elseif ($isSubDoctrineEntity) {
                                                $arrayId .= strtoupper(substr($subFunction, 3)) . ' [  id :' . $subAtribut->getId() . ' ] & ';
                                            } else {
                                                $arrayId .= strtoupper(substr($subFunction, 3)) . ' :' . $subAtribut . ' & ';
                                            }
                                        }
                                    }
                                }
                            }
                            $arrayId .= ' |-| ';
                        }
                    } else {
                        $arrayId = 'goto previus update';
                    }
                    $content[$attrTitle] = $arrayId;
                } elseif ($isArray) {
                    $arrayId = '';
                    foreach ($attribut as $value) {
                        $arrayId .= 'valeur:' . $value . ' | ';
                    }
                    $content[$attrTitle] = $arrayId;
                } elseif (!$isMetadata) {
                    if (($isString && strlen($attribut) > 300 && $isNew) || ($isString && strlen($attribut) < 300) || (!$isString)) {
                        $content[$attrTitle] = $attribut;
                    } else {
                        $content[$attrTitle] = 'goto previus update';
                    }
                } else {
                    // var_dump($attribut);
                    // exit();
                }
            }
        }

        return $content;
    }

    private function isEntity($class)
    {
        if (is_object($class)) {
            $class = ClassUtils::getClass($class);

            return !$this->em->getMetadataFactory()->isTransient($class);
        } else {
            return false;
        }
    }
}
