<?php

use App\Models\Panel;

function createQueueGroup(Panel $panel, string $presenterName, int $groupOrder, int $queueOrder)
{
    $presenter = $panel->participants()->create([
        'name' => $presenterName,
        'role' => 'presenter',
        'group_order' => $groupOrder,
        'queue_order' => $queueOrder,
        'exam_date' => now()->toDateString(),
        'status' => 'waiting',
    ]);

    $observer1 = $panel->participants()->create([
        'name' => $presenterName.' Observer 1',
        'role' => 'observer',
        'group_order' => $groupOrder,
        'queue_order' => $queueOrder + 1,
        'exam_date' => now()->toDateString(),
        'status' => 'waiting',
    ]);

    $observer2 = $panel->participants()->create([
        'name' => $presenterName.' Observer 2',
        'role' => 'observer',
        'group_order' => $groupOrder,
        'queue_order' => $queueOrder + 2,
        'exam_date' => now()->toDateString(),
        'status' => 'waiting',
    ]);

    return [$presenter, $observer1, $observer2];
}

test('queue counts use presenter rows as the business unit', function () {
    $panel = Panel::create([
        'name' => 'Panel RPL Kelas 10',
        'grade' => '10',
        'major' => 'RPL',
    ]);

    createQueueGroup($panel, 'Andi', 1, 1);
    createQueueGroup($panel, 'Budi', 2, 4);

    expect($panel->activePresenterCount())->toBe(2)
        ->and($panel->waitingPresenterCount())->toBe(2)
        ->and($panel->participants()->count())->toBe(6);
});

test('reordering presenter groups moves observers with their presenter', function () {
    $panel = Panel::create([
        'name' => 'Panel RPL Kelas 10',
        'grade' => '10',
        'major' => 'RPL',
    ]);

    [$andi] = createQueueGroup($panel, 'Andi', 1, 1);
    [$budi] = createQueueGroup($panel, 'Budi', 2, 4);

    $panel->reorderPresenterGroups([$budi->id, $andi->id]);

    expect($budi->fresh()->queue_order)->toBe(1)
        ->and($panel->observersFor($budi->fresh())->pluck('queue_order')->all())->toBe([2, 3])
        ->and($andi->fresh()->queue_order)->toBe(4)
        ->and($panel->observersFor($andi->fresh())->pluck('queue_order')->all())->toBe([5, 6]);
});

test('skipping and recalling works on the whole presenter observer group', function () {
    $panel = Panel::create([
        'name' => 'Panel RPL Kelas 10',
        'grade' => '10',
        'major' => 'RPL',
    ]);

    [$andi, $andiObserver1, $andiObserver2] = createQueueGroup($panel, 'Andi', 1, 1);
    createQueueGroup($panel, 'Budi', 2, 4);

    $panel->advanceQueue();
    $panel->skipPresenter();

    expect($andi->fresh()->status)->toBe('skipped')
        ->and($andiObserver1->fresh()->status)->toBe('skipped')
        ->and($andiObserver2->fresh()->status)->toBe('skipped');

    $panel->recallSkippedPresenter($andi->id);

    expect($andi->fresh()->status)->toBe('presenting')
        ->and($andiObserver1->fresh()->status)->toBe('observing')
        ->and($andiObserver2->fresh()->status)->toBe('observing');
});
