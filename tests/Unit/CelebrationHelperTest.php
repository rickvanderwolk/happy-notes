<?php

namespace Tests\Unit;

use App\Helpers\CelebrationHelper;
use PHPUnit\Framework\TestCase;

class CelebrationHelperTest extends TestCase
{
    public function test_milestones_are_99_and_repdigits_from_222(): void
    {
        foreach ([99, 222, 333, 999, 1111, 2222, 9999, 11111] as $count) {
            $this->assertTrue(CelebrationHelper::isMilestone($count), "{$count} should be a milestone");
        }

        foreach ([0, 1, 11, 98, 100, 111, 221, 1000, 1112] as $count) {
            $this->assertFalse(CelebrationHelper::isMilestone($count), "{$count} should not be a milestone");
        }
    }

    public function test_highest_milestone_up_to_a_count(): void
    {
        $this->assertSame(0, CelebrationHelper::highestMilestoneUpTo(0));
        $this->assertSame(0, CelebrationHelper::highestMilestoneUpTo(98));
        $this->assertSame(99, CelebrationHelper::highestMilestoneUpTo(99));
        $this->assertSame(99, CelebrationHelper::highestMilestoneUpTo(150));
        $this->assertSame(333, CelebrationHelper::highestMilestoneUpTo(437));
        $this->assertSame(999, CelebrationHelper::highestMilestoneUpTo(1110));
        $this->assertSame(2222, CelebrationHelper::highestMilestoneUpTo(2500));
    }

    public function test_every_milestone_has_a_message(): void
    {
        $this->assertSame('Not one problem.', CelebrationHelper::milestoneMessage(99));
        $this->assertSame('Make a wish.', CelebrationHelper::milestoneMessage(1111));
        $this->assertSame("Okay, now you're just showing off.", CelebrationHelper::milestoneMessage(22222));
    }
}
