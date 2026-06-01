<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */

namespace XTS_BUILD\Telegram\Bot\Objects;

/**
 * Class GameHighScore.
 *
 * @property int  $position Position in high score table for the game.
 * @property User $user     User
 * @property int  $score    Score
 */
class GameHighScore extends BaseObject
{
    /**
     * {@inheritdoc}
     */
    public function relations()
    {
        return [
            'user' => User::class,
        ];
    }
}
