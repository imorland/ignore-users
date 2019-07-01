<?php

/*
 * This file is part of fof/ignore-users.
 *
 * Copyright (c) 2019 FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\IgnoreUsers\Listener;

use Flarum\Api\Controller;
use Flarum\Api\Event\WillGetData;
use Flarum\Api\Event\WillSerializeData;
use Flarum\Api\Event\Serializing;
use Flarum\Api\Serializer\UserSerializer;
use Flarum\Event\GetApiRelationship;
use Flarum\Event\GetModelRelationship;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Collection;

class AddIgnoredUsersRelationship
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        // TODO need to configure ignored_at via ConfigureModelDates?
        $events->listen(GetModelRelationship::class, [$this, 'getModelRelationship']);
        $events->listen(WillSerializeData::class, [$this, 'prepareApiData']);
        $events->listen(Serializing::class, [$this, 'prepareApiAttributes']);
    }

    /**
     * @param  GetModelRelationship $event
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|null
     */
    public function getModelRelationship(GetModelRelationship $event)
    {
        if ($event->isRelationship(User::class, 'ignoredUsers')) {
            return $event->model->belongsToMany(User::class, 'ignored_user', 'user_id', 'ignored_user_id')
                ->withPivot('ignored_at');
        }
    }

    /**
     * @param WillSerializeData $event
     */
    public function prepareApiData(WillSerializeData $event)
    {
        if ($event->isController(Controller\ListUsersController::class) || $event->isController(Controller\ShowUserController::class)) {
            $actor = $event->request->getAttribute('actor');
            $actor->load('ignoredUsers');
        }
    }

    /**
     * @param Serializing $event
     */
    public function prepareApiAttributes(Serializing $event)
    {
        if ($event->isSerializer(UserSerializer::class)) {
            $canIgnored = !$event->model->can('notBeIgnored');
            $event->attributes['ignored'] = $canIgnored && $event->actor->ignoredUsers->contains($event->model);
        }
    }
}