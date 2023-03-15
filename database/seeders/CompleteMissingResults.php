<?php

namespace Database\Seeders;

use App\Http\Controllers\Establishments\EstablishmentController;
use App\Models\Establishments\Establishment;
use App\Models\Establishments\Results;
use App\Models\Forms\Question;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompleteMissingResults extends Seeder
{
    protected $establishmentController;

    public function __construct(EstablishmentController $establishmentController)
    {
        $this->establishmentController = $establishmentController;

    }

    /**
     * Run the database seeds.
     *
     * @return void
     * @throws \Exception
     */
    public function run()
    {
        //
        try {
            DB::beginTransaction();
            $establishments = Establishment::with(['results'])->get();
            foreach ($establishments as $establishment) {
                $forms = $establishment->forms();
                $maxIntent = $forms->max('attempts');
                if ($maxIntent){
                    $idIntent = $forms->where('attempts', $maxIntent)->first()->pivot->id;
                    $percentage = $this->establishmentController->showPercentageForm($establishment->id, $idIntent)->original['percentage'];
                    if ($percentage >= 100) {
                        $results = $establishment->results;
                        if ($results) {
                            $results = $results->where('resultable_type', Question::class);
                            foreach ($results as $result) {
                                if ($result->resultable->type == 'rango_1_5' && $result->score == null) {
                                    $result->score = 0;
                                    $result->save();
                                } else if ($result->resultable->type == 'si_no' && $result->score == null) {
                                    $result->score = 0;
                                    $result->save();
                                }
                            }
                        }
                    }
                }
            }
            DB::commit();
        }catch (\Exception $exception){
            DB::rollBack();
            throw  new \Exception($exception->getMessage());
        }
    }
}
