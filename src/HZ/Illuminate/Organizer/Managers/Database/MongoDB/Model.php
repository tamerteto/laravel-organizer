<?php
namespace HZ\Illuminate\Organizer\Managers\Database\MongoDB;

use DB;
use HZ\Illuminate\Organizer\Traits\ModelTrait;
use Jenssegers\Mongodb\Eloquent\Model as BaseModel;

abstract class Model extends BaseModel
{
    use ModelTrait {
        boot as TraitBoot;
    }
    
    /**
     * Created By column
     * Set it to false if this column doesn't exist in the table
     *
     * @const string|bool
     */
    const CREATED_BY = 'created_by';

    /**
     * Updated By column
     * Set it to false if this column doesn't exist in the table
     *
     * @const string|bool
     */
    const UPDATED_BY = 'updated_by';

    /**
     * Deleted By column
     * Set it to false if this column doesn't exist in the table
     *
     * @const string|bool
     */
    const DELETED_BY = 'deleted_by';

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'createdAt';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'updatedAt';

    /**
     * The name of the "deleted at" column.
     *
     * @var string
     */
    const DELETED_AT = 'deletedAt';

    /**
     * Created By column
     * Set it to false if this column doesn't exist in the table
     *
     * @var string|bool
     */
    protected $createdBy = 'createdBy';

    /**
     * Updated By column
     * Set it to false if this column doesn't exist in the table
     *
     * @var string|bool
     */
    protected $updatedBy = 'updatedBy';

    /**
     * Deleted By column
     * Set it to false if this column doesn't exist in the table
     *
     * @var string|bool
     */
    protected $deletedBy = 'deletedBy';

    /**
     * Disable guarded fields
     *
     * @var array
     */
    protected $guarded = [];
    
    /**
     * {@inheritDoc}
     */
    public static function boot() 
    {
        static::TraitBoot();

        // Create an auto increment id on creating new document
         
        // before creating, we will check if the created_by column has value
        // if so, then we will update the column for the current user id
        static::creating(function ($model) {
            $model->id = $model->nextId();
        });
    }

    /**
     * Create and return new id for the current model
     * 
     * @return int
     */
    public function nextId(): int
    {
        $newId = $this->getNextId();

        $lastId = $newId - 1;

        $ids = DB::collection('ids');

        $collection = $this->getTable();

        if (! $lastId) {
            $ids->insert([
                'collection' => $collection,
                'id' => $newId,
            ]);
        } else {
            $ids->where('collection', $collection)->update([
                'id' => $newId
            ]);
        }
        
        return $newId;
    }

    /**
     * Get next id
     * 
     * @return int
     */
    public function getNextId(): int
    {
        return ((int) $this->lastInsertId()) + 1;
    }

    /**
     * Get last insert id of the given collection name
     * 
     * @return  int
     */
    public function lastInsertId(): int 
    {
        $ids = DB::collection('ids');

        $info = $ids->where('collection', $this->getTable())->first();

        return $info ? $info['id'] : 0;
    }

    /**
     * Get model info
     * 
     * @return mixed
     */
    public function info()
    {
        return $this->getAttributes();
    }

    /**
     * {@inheritDoc}
     */
    public static function find($id)
    {
        return static::where('id', (int) $id)->first();
    }

    /**
     * Get user by id that will be used with created by, updated by and deleted by
     * 
     * @return int
     */
    protected function byUser()
    {
        $user = user();
        if (! $user) return [];
        if (method_exists($user, 'sharedInfo')) {
            $info = $user->sharedInfo();
        } else {
            $info = $user->info();
        }
        
        return $info;
    } 
}