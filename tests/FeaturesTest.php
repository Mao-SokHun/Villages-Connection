<?php

use PHPUnit\Framework\TestCase;

class FeaturesTest extends TestCase
{
    public function testBuildCommentTreeGroupsReplies()
    {
        $comments = array(
            array('id' => 1, 'parent_id' => null, 'content' => 'root'),
            array('id' => 2, 'parent_id' => 1, 'content' => 'reply'),
            array('id' => 3, 'parent_id' => null, 'content' => 'root2'),
        );

        $tree = build_comment_tree($comments);
        $this->assertCount(2, $tree);
        $this->assertCount(1, $tree[0]['replies']);
        $this->assertSame(2, (int) $tree[0]['replies'][0]['id']);
    }
}
