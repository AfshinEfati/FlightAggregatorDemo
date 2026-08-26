<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchFlightsRequest;
use App\Http\Resources\FlightResource;
use App\Services\FlightSearchService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FlightController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/v1/flights",
     *      operationId="searchFlights",
     *      tags={"Flights"},
     *      summary="Search for flights",
     *      description="Returns a list of flights matching the search criteria",
     *      @OA\Parameter(
     *          name="origin",
     *          description="Origin airport code (3 letters)",
     *          required=true,
     *          in="query",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Parameter(
     *          name="destination",
     *          description="Destination airport code (3 letters)",
     *          required=true,
     *          in="query",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Parameter(
     *          name="date",
     *          description="Departure date (Y-m-d)",
     *          required=false,
     *          in="query",
     *          @OA\Schema(type="string", format="date")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Validation error"
     *      )
     * )
     */
    public function search(
        SearchFlightsRequest $request,
        FlightSearchService $searchService
    ): AnonymousResourceCollection {
        $flights = $searchService->search($request->validated());

        return FlightResource::collection($flights);
    }
}
