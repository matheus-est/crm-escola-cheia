<?php

declare(strict_types=1);

namespace App\Enums;

enum OutcomeActionType: string
{
    case CreateTask = 'create_task';
    case MoveStatus = 'move_status';
    case CancelTasks = 'cancel_tasks';
    case OpenWindow = 'open_window';
    case CompleteTasksOfType = 'complete_tasks';
    case AssertNoFutureTask = 'assert_no_future_task';
}
