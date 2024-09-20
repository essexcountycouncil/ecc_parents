<?php

namespace Drupal\Tests\ecc_parents\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\NodeInterface;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\pathauto\Functional\PathautoTestHelperTrait;

/**
 * Check guide pages are published/unpublished with their guide overview.
 *
 * @group ecc_parents
 */
class ParentChildTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;
  use PathautoTestHelperTrait;

  /**
   * {@inheritdoc}
   *
   * @todo Resolve schema errors that are not related to the test.
   */
  // phpcs:ignore
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'field',
    'text',
    'link',
    'user',
    'node',
    'options',
    'filter',
    'views',
    'symfony_mailer',
    'content_moderation',
    'ecc_content_moderation',
    'workflows',
    'localgov_core',
    'localgov_guides',
    'localgov_workflows',
    'localgov_workflows_notifications',
    'ecc_parents',
  ];

  /**
   * Node storage.
   *
   * @var \Drupal\node\NodeStorage
   */
  protected $storage;

  /**
   * The ecc_parents.parents service.
   *
   * @var \Drupal\ecc_parents\ParentsInterface
   */
  protected $parents;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->storage = $this->container->get('entity_type.manager')->getStorage('node');

    $this->parents = $this->container->get('ecc_parents.parents');

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('content_moderation_state');
    $this->installEntitySchema('workflow');
    $this->installEntitySchema('localgov_service_contact');
    $this->installSchema('node', ['node_access']);
    $this->installConfig([
      'filter',
      'node',
      'localgov_guides',
      'ecc_parents',
    ]);
  }

  /**
   * Test programmatic parent addition.
   */
  public function testParentChildPublishing() {

    /** @var \Drupal\node\NodeInterface $overview */
    $overview = $this->createNode([
      'title' => 'Overview',
      'type' => 'localgov_guides_overview',
      'moderation_state' => 'published',
      'status' => TRUE,
    ]);

    /** @var \Drupal\node\NodeInterface $page_with_parent */
    $page_with_parent = $this->createNode([
      'title' => 'Page 1',
      'type' => 'localgov_guides_page',
      'localgov_guides_parent' => ['target_id' => $overview->id()],
      'field_publish_with_parent' => TRUE,
      'moderation_state' => 'published',
      'status' => TRUE,
    ]);

    /** @var \Drupal\node\NodeInterface $page_not_with_parent */
    $page_not_with_parent = $this->createNode([
      'title' => 'Page 2',
      'type' => 'localgov_guides_page',
      'localgov_guides_parent' => ['target_id' => $overview->id()],
      'field_publish_with_parent' => FALSE,
      'moderation_state' => 'published',
      'status' => TRUE,
    ]);

    $overview = $this->reloadNode($overview);
    $page_with_parent = $this->reloadNode($page_with_parent);
    $page_not_with_parent = $this->reloadNode($page_not_with_parent);

    $this->assertTrue(count($this->parents->getChildren($overview)) === 2);
    $this->assertTrue($this->parents->getParent($page_with_parent)->id() === $overview->id());
    $this->assertTrue($this->parents->getParent($page_not_with_parent)->id() === $overview->id());

    $this->assertTrue($overview->isPublished());
    $this->assertTrue($page_with_parent->isPublished());
    $this->assertTrue($page_not_with_parent->isPublished());

    $overview
      ->set('moderation_state', 'archived')
      ->setUnpublished()
      ->save();
    $page_with_parent = $this->reloadNode($page_with_parent);
    $page_not_with_parent = $this->reloadNode($page_not_with_parent);

    $this->assertFalse($overview->isPublished());
    $this->assertFalse($page_with_parent->isPublished());
    $this->assertTrue($page_not_with_parent->isPublished());

    $page_not_with_parent
      ->set('moderation_state', 'archived')
      ->setUnpublished()
      ->save();
    $this->assertFalse($page_not_with_parent->isPublished());

    $overview
      ->setPublished()
      ->set('moderation_state', 'published')
      ->save();
    $page_with_parent = $this->reloadNode($page_with_parent);
    $page_not_with_parent = $this->reloadNode($page_not_with_parent);

    $this->assertTrue($overview->isPublished());
    $this->assertTrue($page_with_parent->isPublished());
    $this->assertFalse($page_not_with_parent->isPublished());
  }

  /**
   * Reload a node from storage.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node.
   *
   * @return \Drupal\node\NodeInterface
   *   Node.
   */
  protected function reloadNode(NodeInterface $node): NodeInterface {
    $this->storage->resetCache([$node->id()]);
    return $this->storage->load($node->id());
  }

}
