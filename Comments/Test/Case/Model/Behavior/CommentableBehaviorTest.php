<?php

App::uses('CroogoTestCase', 'Croogo.TestSuite');

class CommentableBehaviorTest extends CroogoTestCase {

	public $setupSettings = false;

	public $fixtures = array(
		'plugin.comments.comment',
		'plugin.nodes.node',
		'plugin.users.user',
		'plugin.taxonomy.type',
	);

	public function setUp(): void {
		$this->Comment = ClassRegistry::init('Comments.Comment');
		$this->Comment->bindModel(array(
			'belongsTo' => array(
				'Node' => array(
					'className' => 'Node',
					'foreignKey' => 'foreign_key',
					'conditions' => array(
						'model' => 'Node',
					),
				),
			),
		), false);

		$this->Comment->Node->Behaviors->load('Comments.Commentable');
	}

	public function tearDown(): void {
		ClassRegistry::flush();
	}

/**
 * Test Commentable Add
 */
	public function testCommentableAdd() {
		$count = $this->Comment->find('count', array('recursive' => -1));

		$this->Comment->Node->id = 1;
		$result = $this->Comment->Node->addComment(array(
			'Comment' => array(
				'body' => 'hello world',
				'name' => 'Your name',
				'email' => 'your@email.dev',
				'status' => 1,
				'website' => '/',
				'ip' => '127.0.0.1',
			),
		));

		$this->assertTrue($result);
		$result = $this->Comment->find('count', array('recursive' => -1));
		$this->assertEquals($count + 1, $result);
	}

/**
 */
	public function testCommentableAddWithMissingId() {
		$this->expectException('UnexpectedValueException');
		unset($this->Comment->Node->id);
		$this->Comment->Node->addComment(array());
	}

/**
 * Test Get Type Setting
 */
	public function testGetTypeSetting() {
		$result = $this->Comment->Node->getTypeSetting(array(
			'Node' => array(
				'type' => 'blog',
			),
		));
		$expected = array(
			'commentable' => true,
			'autoApprove' => true,
			'spamProtection' => false,
			'captchaProtection' => false,
		);
		$this->assertEquals($expected, $result);
	}

}
