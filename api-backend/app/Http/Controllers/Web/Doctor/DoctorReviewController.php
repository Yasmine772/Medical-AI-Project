<?php

namespace App\Http\Controllers\Web\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Doctor\SubmitReviewRequest;
use App\Services\Api\AiService;
use App\Services\Web\DoctorReviewService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorReviewController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected DoctorReviewService $reviewService,
        protected AiService $aiService
    ) {}

    /**
     * Display a listing of the diagnosis sessions for the authenticated doctor.
     * @param Request $request(status, date_filter)
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $lang = $request->query('language_code', 'en');
        $status = $request->query('status');
        $dateFilter = $request->query('date_filter');

        $allowed = ['all', 'urgent', 'pending', 'completed'];
        if ($status !== null && !in_array($status, $allowed, true)) {
            return $this->errorResponse('Invalid status filter!', null, 422);
        }

        $allowedDates = ['today', 'last_7_days', 'last_30_days'];
        if ($dateFilter !== null && !in_array($dateFilter, $allowedDates, true)) {
            return $this->errorResponse('Invalid date filter!', null, 422);
        }

        $reviews = $this->reviewService->reviews($lang, $status, $dateFilter);

        if ($reviews === null) {
            return $this->errorResponse('Doctor not found!', null, 404);
        }

        if (empty($reviews)) {
            return $this->errorResponse('No cases found for this doctor!', null, 404);
        }

        return $this->successResponse($reviews, 'Reviews retrieved successfully', 200);
    }

    public function stats(Request $request): JsonResponse
    {
        $lang = $request->query('language_code', 'en');
        $dateFilter = $request->query('date_filter', 'today');

        $allowedDates = ['today', 'last_7_days', 'last_30_days'];
        if (!in_array($dateFilter, $allowedDates, true)) {
            return $this->errorResponse('Invalid date filter!', null, 422);
        }

        $stats = $this->reviewService->stats($lang, $dateFilter);

        if ($stats === null) {
            return $this->errorResponse('Doctor not found!', null, 404);
        }

        return $this->successResponse($stats, 'Statistics retrieved successfully', 200);
    }

    public function show(Request $request, string $sessionHash): JsonResponse
    {
        $lang = $request->query('language_code', 'en');
        $review = $this->reviewService->reviewDetail($sessionHash, $lang);

        if ($review === null) {
            return $this->errorResponse('Review not found!', null, 404);
        }

        return $this->successResponse($review, 'Review retrieved successfully', 200);
    }

    public function getPdf(Request $request, string $sessionHash)
    {
        $session = $this->reviewService->sessionForDoctor($sessionHash);

        if (!$session) {
            return $this->errorResponse('Review not found!', null, 404);
        }

        $result = $this->aiService->downloadReport($sessionHash, $request->query('language_code', 'en'));

        if ($result === null) {
            return $this->errorResponse('Report download service error', null, 503);
        }

        return $result;
    }

    public function submit(SubmitReviewRequest $request, string $sessionHash): JsonResponse
    {
        $review = $this->reviewService->submitReview($sessionHash, $request->validated());

        if ($review === null) {
            return $this->errorResponse('Review not found!', null, 404);
        }

        return $this->successResponse($review, 'Review submitted successfully', 200);
    }
}
