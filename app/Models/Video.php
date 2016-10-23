<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use App\Traits\Orderable;

class Video extends Model
{
	use SoftDeletes,Searchable,Orderable;

	protected $fillable = [
		'title',
		'description',
		'uid',
		'video_filename',
		'processed',
		'visibility',
		'allow_votes',
		'allow_comments',
		'processed_percentage',
	];

    public function channel()
    {
    	return $this->belongsTo(Channel::class);
    }

    public function getRouteKeyName()
    {
    	return 'uid';
    }

    public function isProcessed()
    {
    	return $this->processed;
    }

    public function processedPercentage()
    {
    	return $this->processed_percentage;
    }

    public function getThumbnail()
    {
    	if (!$this->isProcessed()) {
    		return config('adhiwidi.buckets.videos') . '/default_thumbnail.png';
    	}

    	return config('adhiwidi.buckets.videos') . '/' . $this->video_id . '_1.jpg';
    }

    public function votesAllowed()
    {
        return (bool) $this->allow_votes;
    }

    public function commentsAllowed()
    {
        return (bool) $this->allow_comments;
    }

    public function isPrivate()
    {
        return $this->visibility === 'private';
    }

    public function ownedByUser(User $user)
    {
        return $this->channel->user->id === $user->id;
    }

    public function canBeAccessed($user = null)
    {
        if (!$user && $this->isPrivate()) {
            return false;
        }

        if ($user && $this->isPrivate() && ($user->id !== $this->channel->user->id)) {
            return false;
        }

        return true;
    }

    public function getStreamUrl()
    {
        return config('adhiwidi.buckets.videos') . '/' . $this->video_id . '.mp4';
    }

    public function views()
    {
        return $this->hasMany(VideoView::class);
    }

    public function viewCount()
    {
        return $this->views->count();
    }

    public function votes()
    {
        return $this->morphMany(Vote::class, 'voteable');
    }

    public function upVotes()
    {
        return $this->votes->where('type', 'up');
    }
    public function downVotes()
    {
        return $this->votes->where('type', 'down');
    }

    public function voteFromUser(User $user)
    {
        return $this->votes()->where('user_id', $user->id);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable')->whereNull('reply_id');
    }

    public function scopeProcessed($query)
    {
        return $query->where('processed', true);
    }

    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    public function scopeVisible($query)
    {
        return $query->processed()->public();
    }
}

