<?php

class TeamController extends HTMLController {

    public function showAction(Team $team) {
        $this->drawHeader("Teams :: " . $team->getName());

        echo "<div class='team_name'>" . $team->getName() . "</div> <br />";
        echo "<div class='team_rating'> ELO: " . $team->getElo() . "</div><br />";
        $leader = $team->getLeader();
        echo "<div class='team_leader'>Leader: <a href='" . $leader->getURL() . "'>" . $leader->getUsername() . "</a></div><br />";
        echo "Matches: <a href='" . $team->getMatchesURL() . "'>" . $team->getNumTotalMatches() . "</a><br />";
        echo "Members: " . $team->getNumMembers() . "<br />";
        echo "Activity: " . $team->getActivity() . "<br />";
        echo "Created: " . $team->getCreationDate() . "<br />";
        echo "<br />";
    }

    public function listAction() {
        $this->drawHeader("Teams");

        $teams = Team::getTeams();
    ?>
    <div class="table teams">
        <ul>
            <li>Name</li>
            <li>Rating</li>
            <li>Leader</li>
            <li>Members</li>
            <li>Matches</li>
            <li>Activity</li>
        </ul>

        <?php
        foreach ($teams as $team)
        {
            $leader = $team->getLeader();

            echo '<ul>';
            echo '    <li><a href="' . $team->getURL() . '">' . $team->getName() . '</a></li>';
            echo '    <li>' . $team->getElo() . '</li>';
            echo '    <li>' . $leader->getUsername() . '</li>';
            echo '    <li>' . $team->getNumMembers() . '</li>';
            echo '    <li><a href="' . $team->getMatchesURL() . '">' . $team->getNumTotalMatches() . '</a></li>';
            echo '    <li>' . $team->getActivity() . '</li>';
            echo '</ul>';
        }

        echo '</div>';
    }
}
