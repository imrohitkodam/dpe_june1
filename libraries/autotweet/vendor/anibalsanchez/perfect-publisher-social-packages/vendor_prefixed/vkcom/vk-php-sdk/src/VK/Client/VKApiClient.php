<?php /* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */

namespace XTS_BUILD\VK\Client;

use XTS_BUILD\VK\Client\Enums\VKLanguage;
use XTS_BUILD\VK\Actions\Account;
use XTS_BUILD\VK\Actions\Ads;
use XTS_BUILD\VK\Actions\Apps;
use XTS_BUILD\VK\Actions\Auth;
use XTS_BUILD\VK\Actions\Board;
use XTS_BUILD\VK\Actions\Database;
use XTS_BUILD\VK\Actions\Docs;
use XTS_BUILD\VK\Actions\Fave;
use XTS_BUILD\VK\Actions\Friends;
use XTS_BUILD\VK\Actions\Gifts;
use XTS_BUILD\VK\Actions\Groups;
use XTS_BUILD\VK\Actions\Leads;
use XTS_BUILD\VK\Actions\Likes;
use XTS_BUILD\VK\Actions\Market;
use XTS_BUILD\VK\Actions\Messages;
use XTS_BUILD\VK\Actions\Newsfeed;
use XTS_BUILD\VK\Actions\Notes;
use XTS_BUILD\VK\Actions\Notifications;
use XTS_BUILD\VK\Actions\Orders;
use XTS_BUILD\VK\Actions\Pages;
use XTS_BUILD\VK\Actions\Photos;
use XTS_BUILD\VK\Actions\Places;
use XTS_BUILD\VK\Actions\Polls;
use XTS_BUILD\VK\Actions\Search;
use XTS_BUILD\VK\Actions\Secure;
use XTS_BUILD\VK\Actions\Stats;
use XTS_BUILD\VK\Actions\Status;
use XTS_BUILD\VK\Actions\Storage;
use XTS_BUILD\VK\Actions\Stories;
use XTS_BUILD\VK\Actions\Streaming;
use XTS_BUILD\VK\Actions\Users;
use XTS_BUILD\VK\Actions\Utils;
use XTS_BUILD\VK\Actions\Video;
use XTS_BUILD\VK\Actions\Wall;
use XTS_BUILD\VK\Actions\Widgets;

class VKApiClient {
    protected const API_VERSION = '5.101';
    protected const API_HOST = 'https://api.vk.com/method';

    /**
     * @var VKApiRequest
     */
    private $request;

    /**
     * @var Account
     */
    private $account;

    /**
     * @var Ads
     */
    private $ads;

    /**
     * @var Apps
     */
    private $apps;

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var Board
     */
    private $board;

    /**
     * @var Database
     */
    private $database;

    /**
     * @var Docs
     */
    private $docs;

    /**
     * @var Fave
     */
    private $fave;

    /**
     * @var Friends
     */
    private $friends;

    /**
     * @var Gifts
     */
    private $gifts;

    /**
     * @var Groups
     */
    private $groups;

    /**
     * @var Leads
     */
    private $leads;

    /**
     * @var Likes
     */
    private $likes;

    /**
     * @var Market
     */
    private $market;

    /**
     * @var Messages
     */
    private $messages;

    /**
     * @var Newsfeed
     */
    private $newsfeed;

    /**
     * @var Notes
     */
    private $notes;

    /**
     * @var Notifications
     */
    private $notifications;

    /**
     * @var Orders
     */
    private $orders;

    /**
     * @var Pages
     */
    private $pages;

    /**
     * @var Photos
     */
    private $photos;

    /**
     * @var Places
     */
    private $places;

    /**
     * @var Polls
     */
    private $polls;

    /**
     * @var Search
     */
    private $search;

    /**
     * @var Secure
     */
    private $secure;

    /**
     * @var Stats
     */
    private $stats;

    /**
     * @var Status
     */
    private $status;

    /**
     * @var Storage
     */
    private $storage;

    /**
     * @var Stories
     */
    private $stories;

    /**
     * @var Streaming
     */
    private $streaming;

    /**
     * @var Users
     */
    private $users;

    /**
     * @var Utils
     */
    private $utils;

    /**
     * @var Video
     */
    private $video;

    /**
     * @var Wall
     */
    private $wall;

    /**
     * @var Widgets
     */
    private $widgets;

    /**
     * VKApiClient constructor.
     * @param string $api_version
     * @param string|null $language
     */
    public function __construct(string $api_version = self::API_VERSION, ?string $language = null) {
        $this->request = new VKApiRequest($api_version, $language, self::API_HOST);
    }

    /**
     * @return VKApiRequest
     */
    public function getRequest(): VKApiRequest {
        return $this->request;
    }

    /**
     * @return Account
     */
    public function account(): Account {
        if (!$this->account) {
            $this->account = new Account($this->request);
        }

        return $this->account;
    }

    /**
     * @return Ads
     */
    public function ads(): Ads {
        if (!$this->ads) {
            $this->ads = new Ads($this->request);
        }

        return $this->ads;
    }

    /**
     * @return Apps
     */
    public function apps(): Apps {
        if (!$this->apps) {
            $this->apps = new Apps($this->request);
        }

        return $this->apps;
    }

    /**
     * @return Auth
     */
    public function auth(): Auth {
        if (!$this->auth) {
            $this->auth = new Auth($this->request);
        }

        return $this->auth;
    }

    /**
     * @return Board
     */
    public function board(): Board {
        if (!$this->board) {
            $this->board = new Board($this->request);
        }

        return $this->board;
    }

    /**
     * @return Database
     */
    public function database(): Database {
        if (!$this->database) {
            $this->database = new Database($this->request);
        }

        return $this->database;
    }

    /**
     * @return Docs
     */
    public function docs(): Docs {
        if (!$this->docs) {
            $this->docs = new Docs($this->request);
        }

        return $this->docs;
    }

    /**
     * @return Fave
     */
    public function fave(): Fave {
        if (!$this->fave) {
            $this->fave = new Fave($this->request);
        }

        return $this->fave;
    }

    /**
     * @return Friends
     */
    public function friends(): Friends {
        if (!$this->friends) {
            $this->friends = new Friends($this->request);
        }

        return $this->friends;
    }

    /**
     * @return Gifts
     */
    public function gifts(): Gifts {
        if (!$this->gifts) {
            $this->gifts = new Gifts($this->request);
        }

        return $this->gifts;
    }

    /**
     * @return Groups
     */
    public function groups(): Groups {
        if (!$this->groups) {
            $this->groups = new Groups($this->request);
        }

        return $this->groups;
    }

    /**
     * @return Leads
     */
    public function leads(): Leads {
        if (!$this->leads) {
            $this->leads = new Leads($this->request);
        }

        return $this->leads;
    }

    /**
     * @return Likes
     */
    public function likes(): Likes {
        if (!$this->likes) {
            $this->likes = new Likes($this->request);
        }

        return $this->likes;
    }

    /**
     * @return Market
     */
    public function market(): Market {
        if (!$this->market) {
            $this->market = new Market($this->request);
        }

        return $this->market;
    }

    /**
     * @return Messages
     */
    public function messages(): Messages {
        if (!$this->messages) {
            $this->messages = new Messages($this->request);
        }

        return $this->messages;
    }

    /**
     * @return Newsfeed
     */
    public function newsfeed(): Newsfeed {
        if (!$this->newsfeed) {
            $this->newsfeed = new Newsfeed($this->request);
        }

        return $this->newsfeed;
    }

    /**
     * @return Notes
     */
    public function notes(): Notes {
        if (!$this->notes) {
            $this->notes = new Notes($this->request);
        }

        return $this->notes;
    }

    /**
     * @return Notifications
     */
    public function notifications(): Notifications {
        if (!$this->notifications) {
            $this->notifications = new Notifications($this->request);
        }

        return $this->notifications;
    }

    /**
     * @return Orders
     */
    public function orders(): Orders {
        if (!$this->orders) {
            $this->orders = new Orders($this->request);
        }

        return $this->orders;
    }

    /**
     * @return Pages
     */
    public function pages(): Pages {
        if (!$this->pages) {
            $this->pages = new Pages($this->request);
        }

        return $this->pages;
    }

    /**
     * @return Photos
     */
    public function photos(): Photos {
        if (!$this->photos) {
            $this->photos = new Photos($this->request);
        }

        return $this->photos;
    }

    /**
     * @return Places
     */
    public function places(): Places {
        if (!$this->places) {
            $this->places = new Places($this->request);
        }

        return $this->places;
    }

    /**
     * @return Polls
     */
    public function polls(): Polls {
        if (!$this->polls) {
            $this->polls = new Polls($this->request);
        }

        return $this->polls;
    }

    /**
     * @return Search
     */
    public function search(): Search {
        if (!$this->search) {
            $this->search = new Search($this->request);
        }

        return $this->search;
    }

    /**
     * @return Secure
     */
    public function secure(): Secure {
        if (!$this->secure) {
            $this->secure = new Secure($this->request);
        }

        return $this->secure;
    }

    /**
     * @return Stats
     */
    public function stats(): Stats {
        if (!$this->stats) {
            $this->stats = new Stats($this->request);
        }

        return $this->stats;
    }

    /**
     * @return Status
     */
    public function status(): Status {
        if (!$this->status) {
            $this->status = new Status($this->request);
        }

        return $this->status;
    }

    /**
     * @return Storage
     */
    public function storage(): Storage {
        if (!$this->storage) {
            $this->storage = new Storage($this->request);
        }

        return $this->storage;
    }

    /**
     * @return Stories
     */
    public function stories(): Stories {
        if (!$this->stories) {
            $this->stories = new Stories($this->request);
        }

        return $this->stories;
    }

    /**
     * @return Streaming
     */
    public function streaming(): Streaming {
        if (!$this->streaming) {
            $this->streaming = new Streaming($this->request);
        }

        return $this->streaming;
    }

    /**
     * @return Users
     */
    public function users(): Users {
        if (!$this->users) {
            $this->users = new Users($this->request);
        }

        return $this->users;
    }

    /**
     * @return Utils
     */
    public function utils(): Utils {
        if (!$this->utils) {
            $this->utils = new Utils($this->request);
        }

        return $this->utils;
    }

    /**
     * @return Video
     */
    public function video(): Video {
        if (!$this->video) {
            $this->video = new Video($this->request);
        }

        return $this->video;
    }

    /**
     * @return Wall
     */
    public function wall(): Wall {
        if (!$this->wall) {
            $this->wall = new Wall($this->request);
        }

        return $this->wall;
    }

    /**
     * @return Widgets
     */
    public function widgets(): Widgets {
        if (!$this->widgets) {
            $this->widgets = new Widgets($this->request);
        }

        return $this->widgets;
    }

}
