namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleEvent extends Model
{
    protected $fillable = [
        'title', 
        'case_name', 
        'hearing_time', 
        'status', 
        'notes'
    ];

    protected $casts = [
        'hearing_time' => 'datetime',
    ];
}