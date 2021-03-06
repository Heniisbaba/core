<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\discussions;

use Carbon\Carbon;
use Flarum\Event\ScopeModelVisibility;
use Flarum\Tests\integration\RetrievesAuthorizedUsers;
use Flarum\Tests\integration\TestCase;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;

class ShowTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'discussions' => [
                ['id' => 1, 'title' => 'Empty discussion', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => null, 'comment_count' => 0, 'is_private' => 0],
                ['id' => 2, 'title' => 'Discussion with post', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1, 'is_private' => 0],
                ['id' => 3, 'title' => 'Private discussion', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => null, 'comment_count' => 0, 'is_private' => 1],
                ['id' => 4, 'title' => 'Discussion with hidden post', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 2, 'comment_count' => 1, 'is_private' => 0],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 2, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>a normal reply - too-obscure</p></t>'],
                ['id' => 2, 'discussion_id' => 4, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>a hidden reply - too-obscure</p></t>', 'hidden_at' => Carbon::now()->toDateTimeString()],
            ],
            'users' => [
                $this->normalUser(),
            ],
            'groups' => [
                $this->guestGroup(),
                $this->memberGroup(),
            ],
            'group_user' => [
                ['user_id' => 2, 'group_id' => 3],
            ],
            'group_permission' => [
                ['permission' => 'viewDiscussions', 'group_id' => 2],
                ['permission' => 'viewDiscussions', 'group_id' => 3],
            ]
        ]);
    }

    /**
     * @test
     */
    public function author_can_see_discussion()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions/1', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function guest_cannot_see_empty_discussion()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions/1')
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function guest_cannot_see_hidden_posts()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions/4')
        );

        $json = json_decode($response->getBody()->getContents(), true);

        $this->assertNull(Arr::get($json, 'data.relationships.posts'));
    }

    /**
     * @test
     */
    public function author_can_see_hidden_posts()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions/4', [
                'authenticatedAs' => 2,
            ])
        );

        $json = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(2, Arr::get($json, 'data.relationships.posts.data.0.id'));
    }

    /**
     * @test
     */
    public function when_allowed_guests_can_see_hidden_posts()
    {
        /** @var Dispatcher $events */
        $events = $this->app()->getContainer()->make(Dispatcher::class);

        $events->listen(ScopeModelVisibility::class, function (ScopeModelVisibility $event) {
            if ($event->ability === 'hidePosts') {
                $event->query->whereRaw('1=1');
            }
        });

        $response = $this->send(
            $this->request('GET', '/api/discussions/4')
        );

        $json = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(2, Arr::get($json, 'data.relationships.posts.data.0.id'));
    }

    /**
     * @test
     */
    public function guest_can_see_discussion()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions/2')
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function guests_cannot_see_private_discussion()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions/3')
        );

        $this->assertEquals(404, $response->getStatusCode());
    }
}
