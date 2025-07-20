class Menu extends Model
{
    protected $fillable = ['label', 'icon', 'parent_id', 'route', 'orden'];

    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('orden');
    }

    public function scopePadres($query)
    {
        return $query->whereNull('parent_id')->orderBy('orden');
    }
}