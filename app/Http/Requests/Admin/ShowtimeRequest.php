<?php

namespace App\Http\Requests\Admin;

use App\Models\Movie;
use App\Models\Showtime;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ShowtimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'movie_id' => ['required', 'integer', 'exists:movies,id,deleted_at,NULL'],
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'start_time' => ['required', 'date', 'after:now'],
            'price' => ['required', 'integer', 'min:30000', 'max:500000'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_time.after' => 'Giờ bắt đầu phải ở tương lai.',
            'price.min' => 'Giá vé tối thiểu là 30.000 ₫.',
        ];
    }

    /** Không cho phép hai suất chiếu trùng giờ trong cùng một phòng */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $movie = Movie::find($this->input('movie_id'));
                $start = $this->date('start_time');
                $end = Showtime::calculateEndTime($start, $movie);

                $conflict = Showtime::with('movie')
                    ->where('room_id', $this->input('room_id'))
                    ->when($this->route('showtime'), fn ($q, $current) => $q->whereKeyNot($current->id))
                    ->where('start_time', '<', $end)
                    ->where('end_time', '>', $start)
                    ->first();

                if ($conflict) {
                    $validator->errors()->add('start_time', sprintf(
                        'Phòng đã có suất "%s" từ %s đến %s.',
                        $conflict->movie->title,
                        $conflict->start_time->format('H:i d/m'),
                        $conflict->end_time->format('H:i d/m'),
                    ));
                }
            },
        ];
    }
}
