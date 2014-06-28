<?php

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class InvitationController extends CRUDController
{
    public function showAction(Team $team)
    {
        return array("team" => $team);
    }

    public function listAction()
    {
        return array("teams" => Team::getTeams());
    }

    public function createAction(Player $me)
    {
        return $this->create($me);
    }

    public function deleteAction(Player $me, Team $team)
    {
        return $this->delete($team, $me);
    }

    public function acceptAction(Invitation $invitation, Player $me, FlashBag $flash)
    {
        if (!$me->isTeamless())
            throw new ForbiddenException("You can't join a new team until you leave your current one.");

        if ($invitation->getInvitedPlayer()->getId() != $me->getId())
            throw new ForbiddenException("This invitation isn't for you!");

        $inviter = $invitation->getSentBy()->getEscapedUsername();
        $team    = $invitation->getTeam()->getEscapedName();

        return $this->showConfirmationForm(function () use (&$invitation, &$me, &$flash) {
            $team = $invitation->getTeam();
            $team->addMember($me->getId());

            $message = "You are now a member of {$team->getName()}";
            $flash->add('success', $message);

            return new RedirectResponse($team->getUrl());
        }, "Are you sure you want to accept the invitation from $inviter to join $team?");
    }
}