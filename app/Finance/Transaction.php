<?php

namespace Bookkeeper\Finance;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Nicolaslopezj\Searchable\SearchableTrait;
use Kyslik\ColumnSortable\Sortable;

class Transaction extends Eloquent {

    use SearchableTrait, Sortable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'type', 'amount', 'account_id', 'job_id',
        'received', 'excluded', 'notes', 'invoice', 'created_at',
        'vat_percentage', 'vat_amount', 'total_amount', 'received_at'
    ];

    /**
     * Searchable columns.
     *
     * @var array
     */
    protected $searchable = [
        'columns' => [
            'name' => 10
        ]
    ];

    /**
     * Sortable columns
     *
     * @var array
     */
    protected $sortableColumns = ['created_at', 'name', 'amount'];

    /**
     * Columns to be formatted as timestamps
     *
     * @var array
     */
    protected $dates = ['received_at'];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('filteredByType', function(Builder $query) {
            $query->filteredByType();
        });
    }

    /**
     * Scope for request filter
     *
     * @param Builder $query
     * @param string $type
     * @return Builder
     */
    public function scopeFilteredByType(Builder $query, $type = null)
    {
        $type = $type ?: request('f', 'all');

        if (in_array($type, ['income', 'expense', 'income-i', 'expense-i']))
        {
            $received = ends_with($type, '-i') ? 0 : 1;
            $type = rtrim($type, '-i');

            $query
                ->whereType($type)
                ->whereReceived($received);
        }

        return $query;
    }

    /**
     * Tags relation
     *
     * @return BelongsToMany
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * Accounts relation
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Jobs relation
     *
     * @return BelongsTo
     */
    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * Presents the amount
     *
     * @return string
     */
    public function presentAmount()
    {
        return currency_string_for($this->amount, $this->account_id);
    }

    /**
     * Returns the download link for the invoice
     *
     * @return string
     */
    public function getInvoiceDownloadLinkAttribute()
    {
        return route('bookkeeper.transactions.invoice.download', $this->getKey());
    }

    /**
     * Returns the download link for the invoice
     *
     * @return string
     */
    public function getInvoiceDeleteLinkAttribute()
    {
        return route('bookkeeper.transactions.invoice.delete', $this->getKey());
    }

}
