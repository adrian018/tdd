<?php

namespace App;

trait RecordActivity
{
    /**
     * The project's old attributes.
     *
     * @var array
     */
    public $oldAttributes = [];

    // naming convention for boot method in eloquent
    /**
     * Boot the trait
     */
    public static function bootRecordActivity()
    {
        static::updating(function ($model) {
            $model->oldAttributes = $model->getOriginal();
        });
        // dump(self::recordableEvents());
        
        foreach (self::recordableEvents() as $event) {
            static::$event(function ($model) use ($event) {
                if (class_basename($model) !== 'Project') {
                    $event = "{$event}_" . strtolower(class_basename($model)); // created_task
                }
                $model->recordActivity($event);
            });
        }
    }

    protected static function recordableEvents()
    {
        if (isset(static::$recordableEvents)) {
            return static::$recordableEvents;
        } else {
            return ['created', 'updated', 'deleted'];
        }
    }

    /**
     * Record activity for a project.
     *
     * @param string $description
     */
    public function recordActivity($description)
    {
        $this->activity()->create([
            'description' => $description,
            'changes' => $this->activityChanges(),
            'project_id' => class_basename($this) === 'Project' ? $this->id : $this->project->id
        ]);
    }


    /**
     * The activity feed for the project.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function activity()
    {
        return $this->morphMany(Activity::class, 'subject')->latest();
    }

    /**
     * Fetch the changes to the model.
     *
     * @return array|null
     */
    protected function activityChanges()
    {
        if ($this->wasChanged()) {
            return [
                'before' => array_except(array_diff($this->oldAttributes, $this->getAttributes()), 'updated_at'),
                'after' => array_except($this->getChanges(), 'updated_at')
            ];
        }
    }
}
