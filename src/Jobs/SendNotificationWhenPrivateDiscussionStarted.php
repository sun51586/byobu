<?php

/*
 * This file is part of fof/byobu.
 *
 * Copyright (c) 2019 FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\Byobu\Jobs;

use Flarum\Discussion\Discussion;
use Flarum\Notification\NotificationSyncer;
use Flarum\User\User;
use FoF\Byobu\Notifications\DiscussionCreatedBlueprint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class SendNotificationWhenPrivateDiscussionStarted implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * @var Discussion
     */
    protected $discussion;

    /**
     * @var Collection
     */
    protected $newUsers;

    protected $newGroups;

    protected $settings;

    public function __construct(
        Discussion $discussion,
        Collection $newUsers,
        Collection $newGroups
    ) {
        $this->discussion = $discussion;
        $this->newUsers = $newUsers;
        $this->newGroups = $newGroups;
    }

    public function handle(NotificationSyncer $notifications)
    {
        $userRecipients = User::whereIn('id', $this->newUsers)
            ->whereNotIn('id', [$this->discussion->user_id])
            ->get();

        $groupRecipientUsers = User::leftJoin('group_user', 'users.id', 'group_user.user_id')
            ->whereIn('group_user.group_id', [$this->newGroups])
            ->whereNotIn('users.id', [$this->discussion->user_id])
            ->get();

        if ($userRecipients) {
            $notifications->sync(new DiscussionCreatedBlueprint($this->discussion), $userRecipients->all());
        }

        if ($groupRecipientUsers) {
            $notifications->sync(new DiscussionCreatedBlueprint($this->discussion), $groupRecipientUsers->all());
        }
    }
}
