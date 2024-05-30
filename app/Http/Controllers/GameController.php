<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;
use DateTime;
use Illuminate\Support\Facades\Validator;

/**
* @OA\Info(title="API Cows and Bulls", version="1.0")
*
* @OA\Server(url="http://localhost:8000")
*/

class GameController extends Controller
{
    /**
 * @OA\Post(
 *     path="/api/game/create_game",
 *     tags={"Game"},
 *      summary="Create game",
 *     @OA\RequestBody(
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 @OA\Property(
 *                     property="name",
 *                     type="string",
 *                     example="foo"
 *                 ),
 *                 @OA\Property(
 *                     property="age",
 *                     type="number",
 *                     example=24
 *                 ),
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="OK",
 *         @OA\JsonContent(
 *             @OA\Property(property="user_id", type="number", example="1"),
 *             @OA\Property(property="game_id", type="number", example="1"),
 *             @OA\Property(property="name", type="string", example="foo"),
 *             @OA\Property(property="state", type="string", example="P"),
 *             @OA\Property(property="combination", type="array",
    *                           @OA\Items(
    *                           example = {"item1" : "1","item2" : "2","item3" : "3","item4" : "4"}),
    *                   ),@OA\Property(property="plays", type="array",
    *                           @OA\Items(
    *                           example = {"item1" : "1","item2" : "2","item3" : "3","item4" : "4"}),
    *                   ),
 *             @OA\Property(property="time", type="number", example="0"),
 *             @OA\Property(property="attempts", type="number", example="0"),
 *             @OA\Property(property="evaluation", type="number", example="0")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Error",
 *     ),
 *     security={{"sanctum":{}}}
 * )
 */
    public function create_game(Request $request){
        $validator = Validator::make($request->only('name', 'age'), [
            'name'=>'required|max:255',
            'age'=>'required'
        ]);
        if($validator->fails()){
            return response()->json($validator->errors());
        }
        $combination = $this->generate();

        $time = new DateTime();
        $game = Game::create([
            'user_id' => $request->user()->id,
            'name'=> $request->name,
            'state'=> 'P', //pending
            'combination' => $combination,
            'plays' => [],
            'time' => $time,
            'attempts' => 0,
            'evaluation' => 0,
        ]);
    
        $data = [
            'name'=> $request->name,
            'id' => $game->id,
            'state'=> 'P', //pending
            'plays' => [],
            'time' => $time,
            'attempts' => 0,
        ];
        return response()->json($data, 200);
    }


    /**
 * @OA\Post(
 *     path="/api/game/execute_turn",
 *     tags={"Game"},
 * summary="Execute turn",
 *     @OA\RequestBody(
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                  @OA\Property(
 *                      property="play",
 *                      type="array",
 *                      @OA\Items(
 *                          type="integer"
 *                      )
 *                 ),
 *                 @OA\Property(
 *                     property="id",
 *                     type="number",
 *                     example=1
 *                 ),
 *             )
 *         )
 *     ),
 * 
 *     @OA\Response(
 *         response=200,
 *         description="OK",
 *         @OA\JsonContent(
 *             @OA\Property(property="user_id", type="number", example="1"),
 *             @OA\Property(property="state", type="string", example="P"),
 *             @OA\Property(property="plays", type="array", type = "array",
    *                           @OA\Items(
    *                           example = {"item1" : "1","item2" : "2","item3" : "3","item4" : "4"}),
    *                   ),
 *             @OA\Property(property="result", type="string", example="2B0C"),
 *             @OA\Property(property="attempts", type="number", example="1"),
 *             @OA\Property(property="time", type="number", example="3"),
 *             @OA\Property(property="evaluation", type="number", example="0")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Error",
 *     ),
 *     security={{"sanctum":{}}}
 * )
 */
    public function execute_turn(Request $request){
        $validator = Validator::make($request->only('play', 'id'),[
            'play' => 'required|array',
            'id' => 'required|integer'
        ]);
        if($validator->fails()){
            return response()->json($validator->errors());
        }

        $game = Game::where('id', $request->id)->firstOrFail();
        $time = new DateTime();

        if($game->created_at->diff($time)->s >= 160){
            return response()->json('Time is up, YOU LOSE', 200);
        }
        if($game->state != 'P'){
            return response()->json('Game has already finished', 200);
        }

        if(!$this->plays_validator($request->play)){
            return response()->json('Invalid play', 200);
        }

        $st = $this->get_status($request->play, $game->combination);

        $plays = $game->plays;
        $plays[] = $request->play;
        $attempts = $game->attempts + 1;
        
        $eval = 0;
        if($st['state'] == 'W'){
            $eval = ($game->created_at->diff($time)->s)/2 + $attempts;
        }

        Game::where('id', $request->id)->update([
            'plays' => $plays,
            'state' => $st['state'],
            'time' => $time,
            'attempts' => $attempts,
            'evaluation' => $eval
        ]);
        

        $data = [
            'name'=> $request->name,
            'id'=> $request->user()->id,
            'plays' => $plays,
            'attempts' => $attempts,
            'state' => $st['state'],
            'result' => $st['bulls'] . ' B ' . $st['cows'] . ' C',
            'time' =>  $game->created_at->diff($time)->s,
            'eval' => $eval,
        ];

        return response()->json($data, 200);
    }
    public function get_status($play, $combination){
        $cows = 0;
        $bulls = 0;

        $intersection = array_intersect($play, $combination);

        foreach($intersection as $value){
            if(array_search($value, $play) == array_search($value, $combination)){
                $bulls++;
            }
            else
            $cows++;
        }
        if($bulls == 4){
            $data = [
                'bulls' => $bulls,
                'cows' => $cows,
                'state' => 'W',
            ];
            return $data;
        }
        else{
            $data = [
                'bulls' => $bulls,
                'cows' => $cows,
                'state' => 'P'
            ];
            return $data;
        }
    }
    public function plays_validator($play){
        $unique = array_unique($play);
        if(count($play) == 4 && count($unique) == count($play) && max($play) < 10 && min($play) >= 0)
            return true;        
        return false;
    }
    /**
     * Generates 4 different numbers from 0 to 9
     *
     * @return array
     */
    function generate(){
        $numbers = range(0,9);
        shuffle($numbers);
        return array_slice($numbers, 0, 4);
    }
    /**
    * @OA\Post(
    *     path="/api/game/index",
    *     tags={"Game"},
    *     summary="Game list",
    *     @OA\Response(
    *         response=200,
    *         description="OK",
    *        @OA\JsonContent(
    *           @OA\Property(
    *               type="array",
    *               property="game list",
    *               @OA\Items(
    *                   type="object",
    *                       @OA\Property(
    *                           property = "game_id",
    *                           type = "number",
    *                           example = "1"
    *                   ),@OA\Property(
    *                           property = "user_id",
    *                           type = "number",
    *                           example = "1"
    *                   ),@OA\Property(
    *                           property = "name",
    *                           type = "string",
    *                           example = "foo"
    *                   ),@OA\Property(
    *                           property = "state",
    *                           type = "string",
    *                           example = "W"
    *                   ),@OA\Property(
    *                           property = "attempt",
    *                           type = "number",
    *                           example = "10"
    *                   ),@OA\Property(
    *                           property = "evaluation",
    *                           type = "number",
    *                           example = "25"
    *                   ),@OA\Property(
    *                           property = "combination",
    *                           type = "array",
    *                           @OA\Items(
    *                           example = {"item1" : "1","item2" : "2","item3" : "3","item4" : "4"}),
    *                   ),
    *               ),
    *           ),
    *           
    *        ),
    *     ),
    *     @OA\Response(
    *         response="401",
    *         description="Unauthorized"
    *     ),
    *     security={{"sanctum":{}}}
    * )
    */
    public function index(Request $request){
        $games = Game::where('user_id', $request->user()->id)->get();
        return response()->json($games, 200);
    }
     /**
    * @OA\Get(
    *     path="/api/game/ranking",
    *     tags = {"Game"},
    *     summary="Show ranking",
    *     @OA\Response(
    *         response=200,
    *         description="OK",
    *         @OA\JsonContent(
    *               @OA\Property(
    *               type="array",
    *               property="ranking list",
    *                   @OA\Items(
    *                       type="object",
    *                       @OA\Property(
    *                           property = "name",
    *                           type = "string",
    *                           example = "foo"
    *                   ),@OA\Property(
    *                           property = "evaluation",
    *                           type = "number",
    *                           example = "25"
    *                   ),
    *         ),
    *     ),),),
    *     @OA\Response(
    *         response="default",
    *         description="Error"
    *     ),
    *     security={{"sanctum":{}}}
    * )
    */
    public function ranking(){
        $games = Game::select('name', 'evaluation')
                    ->orderBy('evaluation')
                    ->get();
        return response()->json($games,200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Game $game)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Game $game)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Game $game)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Game $game)
    {
        //
    }
}
