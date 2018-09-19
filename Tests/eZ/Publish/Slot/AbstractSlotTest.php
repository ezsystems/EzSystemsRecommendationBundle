<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Tests\eZ\Publish\Slot;

use PHPUnit\Framework\TestCase;
use eZ\Publish\Core\Base\Tests\PHPUnit5CompatTrait;

abstract class AbstractSlotTest extends TestCase
{
    /** @var \EzSystems\RecommendationBundle\Client\RecommendationClient|\PHPUnit_Framework_MockObject_MockObject */
    protected $client;

    /** @var \EzSystems\RecommendationBundle\eZ\Publish\Slot\Base */
    protected $slot;

    public function setUp()
    {
        $this->client = $this->getMockBuilder('\EzSystems\RecommendationBundle\Client\RecommendationClient')->getMock();
        $this->slot = $this->createSlot();
    }

    public function testReceiveSignal()
    {
        $this->client->expects($this->never())->method('updateContent');
        $this->client->expects($this->never())->method('deleteContent');
        $this->client->expects($this->never())->method('hideLocation');
        $this->client->expects($this->never())->method('unhideLocation');

        $this->slot->receive($this->createSignal());
    }

    /**
     * @dataProvider getUnreceivedSignals
     */
    public function testDoesNotReceiveOtherSignals($signal)
    {
        $this->client->expects($this->never())->method('updateContent');
        $this->client->expects($this->never())->method('deleteContent');
        $this->client->expects($this->never())->method('hideLocation');
        $this->client->expects($this->never())->method('unhideLocation');

        $this->slot->receive($signal);
    }

    public function getReceivedSignals()
    {
        return [
            $this->createSignal(),
        ];
    }

    public function getUnreceivedSignals()
    {
        $arguments = [];

        $signals = $this->getAllSignals();
        foreach ($signals as $signalClass) {
            if (in_array($signalClass, $this->getReceivedSignalClasses())) {
                continue;
            }
            $arguments[] = [new $signalClass()];
        }

        return $arguments;
    }

    /**
     * Asserts that recommendation service is notified about update of specified content objects.
     *
     * @param array $notifications
     */
    protected function assertRecommendationServiceIsNotified(array $notifications)
    {
        $notifications = array_merge([
            'deleteContent' => [],
            'updateContent' => [],
            'hideLocation' => [],
            'unhideLocation' => [],
        ], $notifications);

        foreach ($notifications as $action => $contentIds) {
            if (empty($contentIds)) {
                $this->client->expects($this->never())->method($action);
            } else {
                $this->client
                    ->expects($this->exactly(count($contentIds)))
                    ->method($action)
                    ->willReturnCallback(function ($id) use ($contentIds) {
                        $this->assertContains($id, $contentIds);
                    });
            }
        }
    }

    /**
     * Asserts that recommendation service is notified about delete of specified content objects.
     *
     * @param array $contentIds
     */
    protected function assertContentIsDeleted(array $contentIds)
    {
        $this->client
            ->expects($this->exactly(count($contentIds)))
            ->method('deleteContent')
            ->willReturnCallback(function ($id) use ($contentIds) {
                $this->assertContains($id, $contentIds);
            });
    }

    /**
     * Asserts that recommendation service is notified about update of specified content objects.
     *
     * @param array $contentIds
     */
    protected function assertContentIsUpdated(array $contentIds)
    {
        $this->client
            ->expects($this->exactly(count($contentIds)))
            ->method('updateContent')
            ->willReturnCallback(function ($id) use ($contentIds) {
                $this->assertContains($id, $contentIds);
            });
    }

    protected function createSlot()
    {
        $class = $this->getSlotClass();

        return new $class($this->client);
    }

    abstract protected function createSignal();

    abstract protected function getSlotClass();

    abstract protected function getReceivedSignalClasses();

    /**
     * @return array
     */
    private function getAllSignals()
    {
        return [
            'eZ\Publish\Core\SignalSlot\Signal\URLAliasService\CreateUrlAliasSignal',
            'eZ\Publish\Core\SignalSlot\Signal\URLAliasService\RemoveAliasesSignal',
            'eZ\Publish\Core\SignalSlot\Signal\URLAliasService\CreateGlobalUrlAliasSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ContentTypeService\CreateContentTypeSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ContentTypeService\AddFieldDefinitionSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ContentTypeService\CopyContentTypeSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ContentTypeService\DeleteContentTypeSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ContentTypeService\UpdateContentTypeGroupSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ContentTypeService\DeleteContentTypeGroupSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ContentTypeService\UnassignContentTypeGroupSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ContentTypeService\PublishContentTypeDraftSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ContentTypeService\AssignContentTypeGroupSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ContentTypeService\UpdateFieldDefinitionSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ContentTypeService\UpdateContentTypeDraftSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ContentTypeService\RemoveFieldDefinitionSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ContentTypeService\CreateContentTypeDraftSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ContentTypeService\CreateContentTypeGroupSignal',
            'eZ\Publish\Core\SignalSlot\Signal\LanguageService\EnableLanguageSignal',
            'eZ\Publish\Core\SignalSlot\Signal\LanguageService\UpdateLanguageNameSignal',
            'eZ\Publish\Core\SignalSlot\Signal\LanguageService\CreateLanguageSignal',
            'eZ\Publish\Core\SignalSlot\Signal\LanguageService\DisableLanguageSignal',
            'eZ\Publish\Core\SignalSlot\Signal\LanguageService\DeleteLanguageSignal',
            'eZ\Publish\Core\SignalSlot\Signal\UserService\MoveUserGroupSignal',
            'eZ\Publish\Core\SignalSlot\Signal\UserService\DeleteUserGroupSignal',
            'eZ\Publish\Core\SignalSlot\Signal\UserService\CreateUserGroupSignal',
            'eZ\Publish\Core\SignalSlot\Signal\UserService\UpdateUserGroupSignal',
            'eZ\Publish\Core\SignalSlot\Signal\UserService\UnAssignUserFromUserGroupSignal',
            'eZ\Publish\Core\SignalSlot\Signal\UserService\AssignUserToUserGroupSignal',
            'eZ\Publish\Core\SignalSlot\Signal\UserService\DeleteUserSignal',
            'eZ\Publish\Core\SignalSlot\Signal\UserService\CreateUserSignal',
            'eZ\Publish\Core\SignalSlot\Signal\UserService\UpdateUserSignal',
            'eZ\Publish\Core\SignalSlot\Signal\SectionService\DeleteSectionSignal',
            'eZ\Publish\Core\SignalSlot\Signal\SectionService\CreateSectionSignal',
            'eZ\Publish\Core\SignalSlot\Signal\SectionService\UpdateSectionSignal',
            'eZ\Publish\Core\SignalSlot\Signal\SectionService\AssignSectionSignal',
            'eZ\Publish\Core\SignalSlot\Signal\RoleService\AssignRoleToUserGroupSignal',
            'eZ\Publish\Core\SignalSlot\Signal\RoleService\UpdatePolicySignal',
            'eZ\Publish\Core\SignalSlot\Signal\RoleService\CreateRoleSignal',
            'eZ\Publish\Core\SignalSlot\Signal\RoleService\RemovePolicySignal',
            'eZ\Publish\Core\SignalSlot\Signal\RoleService\UnassignRoleFromUserSignal',
            'eZ\Publish\Core\SignalSlot\Signal\RoleService\AddPolicySignal',
            'eZ\Publish\Core\SignalSlot\Signal\RoleService\UnassignRoleFromUserGroupSignal',
            'eZ\Publish\Core\SignalSlot\Signal\RoleService\UpdateRoleSignal',
            'eZ\Publish\Core\SignalSlot\Signal\RoleService\AssignRoleToUserSignal',
            'eZ\Publish\Core\SignalSlot\Signal\RoleService\DeleteRoleSignal',
            'eZ\Publish\Core\SignalSlot\Signal\TrashService\TrashSignal',
            'eZ\Publish\Core\SignalSlot\Signal\TrashService\EmptyTrashSignal',
            'eZ\Publish\Core\SignalSlot\Signal\TrashService\RecoverSignal',
            'eZ\Publish\Core\SignalSlot\Signal\TrashService\DeleteTrashItemSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ObjectStateService\DeleteObjectStateSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ObjectStateService\CreateObjectStateSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ObjectStateService\DeleteObjectStateGroupSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ObjectStateService\CreateObjectStateGroupSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ObjectStateService\UpdateObjectStateSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ObjectStateService\UpdateObjectStateGroupSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ObjectStateService\SetContentStateSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ObjectStateService\SetPriorityOfObjectStateSignal',
            'eZ\Publish\Core\SignalSlot\Signal\URLWildcardService\TranslateSignal',
            'eZ\Publish\Core\SignalSlot\Signal\URLWildcardService\RemoveSignal',
            'eZ\Publish\Core\SignalSlot\Signal\URLWildcardService\CreateSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ContentService\UpdateContentSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ContentService\CreateContentDraftSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ContentService\AddRelationSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ContentService\CreateContentSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ContentService\DeleteContentSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ContentService\AddTranslationInfoSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ContentService\CopyContentSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ContentService\UpdateContentMetadataSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ContentService\TranslateVersionSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ContentService\PublishVersionSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ContentService\DeleteRelationSignal',
            'eZ\Publish\Core\SignalSlot\Signal\ContentService\DeleteVersionSignal',
            'eZ\Publish\Core\SignalSlot\Signal\LocationService\UpdateLocationSignal',
            'eZ\Publish\Core\SignalSlot\Signal\LocationService\HideLocationSignal',
            'eZ\Publish\Core\SignalSlot\Signal\LocationService\SwapLocationSignal',
            'eZ\Publish\Core\SignalSlot\Signal\LocationService\MoveSubtreeSignal',
            'eZ\Publish\Core\SignalSlot\Signal\LocationService\UnhideLocationSignal',
            'eZ\Publish\Core\SignalSlot\Signal\LocationService\CreateLocationSignal',
            'eZ\Publish\Core\SignalSlot\Signal\LocationService\DeleteLocationSignal',
            'eZ\Publish\Core\SignalSlot\Signal\LocationService\CopySubtreeSignal',
        ];
    }
}
