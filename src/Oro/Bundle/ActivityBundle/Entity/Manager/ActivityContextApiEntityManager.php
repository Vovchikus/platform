<?php

namespace Oro\Bundle\ActivityBundle\Entity\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\ActivityBundle\Event\PrepareContextTitleEvent;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Resolver\EntityTitleResolverInterface;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

class ActivityContextApiEntityManager extends ApiEntityManager
{
    /** @var ActivityManager */
    protected $activityManager;

    /** @var TokenStorageInterface */
    protected $securityTokenStorage;

    /** @var ConfigManager */
    protected $configManager;

    /** @var RouterInterface */
    protected $router;

    /** @var EntityAliasResolver */
    protected $entityAliasResolver;

    /** @var EntityTitleResolverInterface */
    protected $entityTitleResolver;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param ObjectManager                 $om
     * @param ActivityManager               $activityManager
     * @param TokenStorageInterface         $securityTokenStorage
     * @param ConfigManager                 $configManager
     * @param RouterInterface               $router
     * @param EntityAliasResolver           $entityAliasResolver
     * @param EntityTitleResolverInterface  $entityTitleResolver
     * @param DoctrineHelper                $doctrineHelper
     */
    public function __construct(
        ObjectManager $om,
        ActivityManager $activityManager,
        TokenStorageInterface $securityTokenStorage,
        ConfigManager $configManager,
        RouterInterface $router,
        EntityAliasResolver $entityAliasResolver,
        EntityTitleResolverInterface $entityTitleResolver,
        DoctrineHelper $doctrineHelper
    ) {
        parent::__construct(null, $om);

        $this->activityManager      = $activityManager;
        $this->securityTokenStorage = $securityTokenStorage;
        $this->configManager        = $configManager;
        $this->router               = $router;
        $this->entityAliasResolver  = $entityAliasResolver;
        $this->entityTitleResolver  = $entityTitleResolver;
        $this->doctrineHelper       = $doctrineHelper;
    }

    /**
     * Returns the context for the given activity class and id
     *
     * @param string $class The FQCN of the activity entity
     * @param        $id
     *
     * @return array
     */
    public function getActivityContext($class, $id)
    {
        $currentUser = $this->securityTokenStorage->getToken()->getUser();
        $userClass   = ClassUtils::getClass($currentUser);
        $entity      = $this->doctrineHelper->getEntity($class, $id);
        $result = [];

        if (!$entity || !$entity instanceof ActivityInterface) {
            return $result;
        }

        $targets = $entity->getActivityTargetEntities();
        $entityProvider = $this->configManager->getProvider('entity');

        foreach ($targets as $target) {
            $targetClass = ClassUtils::getClass($target);
            $targetId = $target->getId();

            if ($userClass === $targetClass && $currentUser->getId() === $targetId) {
                continue;
            }

            $item          = [];
            $config        = $entityProvider->getConfig($targetClass);
            $safeClassName = $this->entityClassNameHelper->getUrlSafeClassName($targetClass);

            $item['title'] = $this->entityTitleResolver->resolve($target);

            $item['activityClassAlias'] = $this->entityAliasResolver->getPluralAlias($class);
            $item['entityId']           = $id;

            $item['targetId']        = $targetId;
            $item['targetClassName'] = $safeClassName;

            $item['icon'] = $config->get('icon');
            $item['link'] = $this->getContextLink($targetClass, $targetId);

            $item = $this->dispatchContextTitle($item, $targetClass);

            $result[] = $item;
        }

        return $result;
    }

    /**
     * @param string $targetClass The FQCN of the activity target entity
     * @param int    $targetId    The identifier of the activity target entity
     *
     * @return string|null
     */
    protected function getContextLink($targetClass, $targetId)
    {
        $metadata = $this->configManager->getEntityMetadata($targetClass);
        $link     = null;
        if ($metadata) {
            try {
                $route = $metadata->getRoute('view', true);
            } catch (\LogicException $exception) {
                // Need for cases when entity does not have route.
                return null;
            }
            $link = $this->router->generate($route, ['id' => $targetId]);
        } elseif (ExtendHelper::isCustomEntity($targetClass)) {
            $safeClassName = $this->entityClassNameHelper->getUrlSafeClassName($targetClass);
            // Generate view link for the custom entity
            $link = $this->router->generate(
                'oro_entity_view',
                [
                    'id'         => $targetId,
                    'entityName' => $safeClassName

                ]
            );
        }

        return $link;
    }

    /**
     * @param $item
     * @param $targetClass
     * @return array
     */
    protected function dispatchContextTitle($item, $targetClass)
    {
        if ($this->eventDispatcher) {
            $event = new PrepareContextTitleEvent($item, $targetClass);
            $this->eventDispatcher->dispatch(PrepareContextTitleEvent::EVENT_NAME, $event);
            $item = $event->getItem();
        }

        return $item;
    }
}
