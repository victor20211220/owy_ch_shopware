<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT32889;

use Shopware\Core\Framework\Api\Acl\Role\AclRoleDefinition;
use Shopware\Core\Framework\Api\Exception\MissingPrivilegeException;
use Shopware\Core\Framework\Api\Response\ResponseFactoryInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\StateMachine\Api\StateMachineActionController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PatchedStateMachineActionController extends StateMachineActionController
{
    #[Route(path: '/api/_action/state-machine/{entityName}/{entityId}/state', name: 'api.state_machine.states', methods: ['GET'])]
    public function getAvailableTransitions(
        Request $request,
        Context $context,
        string $entityName,
        string $entityId
    ): JsonResponse {
        $this->validatePrivilege($entityName, AclRoleDefinition::PRIVILEGE_READ, $context);

        return parent::getAvailableTransitions($request, $context, $entityName, $entityId);
    }

    #[Route(path: '/api/_action/state-machine/{entityName}/{entityId}/state/{transition}', name: 'api.state_machine.transition_state', methods: ['POST'])]
    public function transitionState(
        Request $request,
        Context $context,
        ResponseFactoryInterface $responseFactory,
        string $entityName,
        string $entityId,
        string $transition
    ): Response {
        $this->validatePrivilege($entityName, AclRoleDefinition::PRIVILEGE_UPDATE, $context);

        return parent::transitionState($request, $context, $responseFactory, $entityName, $entityId, $transition);
    }

    private function validatePrivilege(string $entityName, string $privilege, Context $context): void
    {
        $permission = $entityName . ':' . $privilege;
        if (!$context->isAllowed($permission)) {
            throw new MissingPrivilegeException([$permission]);
        }
    }
}
