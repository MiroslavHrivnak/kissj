<?php

namespace kissj\Participant\Admin;

use kissj\AbstractController;
use kissj\Participant\Guest\GuestService;
use kissj\Participant\Ist\IstService;
use kissj\Participant\ParticipantRepository;
use kissj\Participant\ParticipantService;
use kissj\Participant\Patrol\PatrolService;
use kissj\Payment\PaymentRepository;
use kissj\Payment\PaymentService;
use kissj\User\User;
use Slim\Http\Request;
use Slim\Http\Response;

class AdminController extends AbstractController {
    public $participantService;
    public $participantRepository;
    public $paymentService;
    public $paymentRepository;
    public $patrolService;
    public $istService;
    public $guestService;

    public function __construct(
        ParticipantService $participantService,
        ParticipantRepository $participantRepository,
        PaymentService $paymentService,
        PaymentRepository $paymentRepository,
        PatrolService $patrolService,
        IstService $istService,
        GuestService $guestService
    ) {
        $this->participantService = $participantService;
        $this->participantRepository = $participantRepository;
        $this->paymentService = $paymentService;
        $this->paymentRepository = $paymentRepository;
        $this->patrolService = $patrolService;
        $this->istService = $istService;
        $this->guestService = $guestService;
    }

    public function showDashboard(Response $response) {
        return $this->view->render(
            $response,
            'admin/dashboard-admin.twig',
            [
                'patrols' => $this->patrolService->getAllPatrolsStatistics(),
                'ists' => $this->istService->getAllIstsStatistics(),
                'guests' => $this->guestService->getAllGuestsStatistics(),
            ]
        );
    }

    public function showApproving(
        Response $response,
        ParticipantService $participantService
    ) {
        return $this->view->render($response, 'admin/approving-admin.twig', [
            'closedPatrolLeaders' => $participantService
                ->getAllParticipantsWithStatus(User::ROLE_PATROL_LEADER, USER::STATUS_CLOSED),
            'closedIsts' => $participantService
                ->getAllParticipantsWithStatus(User::ROLE_IST, USER::STATUS_CLOSED),
            'closedGuests' => $participantService
                ->getAllParticipantsWithStatus(User::ROLE_GUEST, USER::STATUS_CLOSED),
        ]);
    }

    public function showPayments(
        Response $response,
        ParticipantService $participantService
    ) {
        $this->view->render($response, 'admin/payments-admin.twig', [
            'approvedPatrolLeaders' => $participantService
                ->getAllParticipantsWithStatus(User::ROLE_PATROL_LEADER, USER::STATUS_APPROVED),
            'approvedIsts' => $participantService
                ->getAllParticipantsWithStatus(User::ROLE_IST, USER::STATUS_APPROVED),
            'approvedGuests' => $participantService
                ->getAllParticipantsWithStatus(User::ROLE_GUEST, USER::STATUS_APPROVED),
        ]);
    }

    public function showCancelPayment(int $paymentId, Response $response) {
        $payment = $this->paymentRepository->find($paymentId);

        return $this->view->render($response, 'admin/cancelPayment-admin.twig', ['payment' => $payment]);
    }

    public function cancelPayment(int $paymentId, Request $request, Response $response) {
        $reason = htmlspecialchars($request->getParam('reason'), ENT_QUOTES);

        $payment = $this->paymentRepository->find($paymentId);
        $this->participantService->cancelPayment($payment, $reason);
        $this->flashMessages->info('Participant payment cancelled, email with reason sent');
        $this->logger->info('Cancelled payment ID '.$paymentId.' for participant with reason: '.$reason);

        return $response->withRedirect(
            $this->router->pathFor('admin-show-payments', ['eventSlug' => $payment->participant->user->event->slug])
        );
    }

    public function confirmPayment(int $paymentId, Response $response) {
        $payment = $this->paymentRepository->find($paymentId);
        $this->participantService->confirmPayment($payment);
        $this->flashMessages->success('Participant payment is confirmed, email about confirmation is sent');
        $this->logger->info('Confirmed payment ID'.$paymentId);

        return $response->withRedirect($this->router->pathFor(
            'admin-show-payments', ['eventSlug' => $payment->participant->user->event->slug])
        );
    }
}
