<?php

class MatchTest extends TestCase
{
    protected $team_a;
    protected $team_b;
    protected $match;
    protected $match_b;
    protected $player_a;
    protected $player_b;

    protected function setUp()
    {
        $this->connectToDatabase();

        $this->player_a = $this->getNewPlayer();
        $this->player_b = $this->getNewPlayer();

        $this->team_a = Team::createTeam("Team A", $this->player_a->getId(), "", "");
        $this->team_b = Team::createTeam("Team B", $this->player_b->getId(), "", "");
    }

    public function testTeamAWin()
    {
        $this->match = Match::enterMatch($this->team_a->getId(), $this->team_b->getId(), 5, 2, 30, $this->player_a->getId());

        $this->assertInstanceOf("Team", $this->match->getTeamA());
        $this->assertInstanceOf("Team", $this->match->getTeamB());

        $this->assertEquals(1225, $this->match->getTeamA()->getElo());
        $this->assertEquals(1175, $this->match->getTeamB()->getElo());

        $this->assertEquals(1225, $this->match->getTeamAEloNew());
        $this->assertEquals(1175, $this->match->getTeamBEloNew());

        $this->assertEquals(1200, $this->match->getTeamAEloOld());
        $this->assertEquals(1200, $this->match->getTeamBEloOLd());

        $this->assertEquals(5, $this->match->getTeamAPoints());
        $this->assertEquals(2, $this->match->getTeamBPoints());

        $this->assertEquals(30, $this->match->getDuration());

        $this->assertEquals(25, $this->match->getEloDiff());

        $this->assertFalse($this->match->isDraw());

        $this->assertEquals($this->team_a->getId(), $this->match->getWinner()->getId());
        $this->assertEquals($this->team_b->getId(), $this->match->getLoser()->getId());
    }

    public function testTeamBWin()
    {
        $this->match = Match::enterMatch($this->team_a->getId(), $this->team_b->getId(), 2, 5, 30, $this->player_a->getId());

        $this->assertEquals(1175, $this->match->getTeamAEloNew());
        $this->assertEquals(1225, $this->match->getTeamBEloNew());

        $this->assertEquals(1200, $this->match->getTeamAEloOld());
        $this->assertEquals(1200, $this->match->getTeamBEloOLd());

        $this->assertEquals(2, $this->match->getTeamAPoints());
        $this->assertEquals(5, $this->match->getTeamBPoints());

        $this->assertEquals(25, $this->match->getEloDiff());

        $this->assertFalse($this->match->isDraw());

        $this->assertEquals($this->team_b->getId(), $this->match->getWinner()->getId());
        $this->assertEquals($this->team_a->getId(), $this->match->getLoser()->getId());
    }

    public function testDraw()
    {
        $this->team_a->changeElo(+10);

        $this->match = Match::enterMatch($this->team_a->getId(), $this->team_b->getId(), 3, 3, 30, $this->player_a->getId());

        $this->assertTrue($this->match->isDraw());

        $this->assertEquals(1, $this->match->getEloDiff());

        $this->assertEquals(1209, $this->match->getTeamAEloNew());
        $this->assertEquals(1201, $this->match->getTeamBEloNew());

        $this->assertEquals(1210, $this->match->getTeamAEloOld());
        $this->assertEquals(1200, $this->match->getTeamBEloOLd());

        $this->assertInstanceOf("Team", $this->match->getWinner());
        $this->assertInstanceOf("Team", $this->match->getLoser());
    }

    public function testDrawReverse()
    {
        $this->team_b->changeElo(+10);

        $this->match = Match::enterMatch($this->team_a->getId(), $this->team_b->getId(), 3, 3, 30, $this->player_a->getId());

        $this->assertTrue($this->match->isDraw());

        $this->assertEquals(1, $this->match->getEloDiff());

        $this->assertEquals(1201, $this->match->getTeamAEloNew());
        $this->assertEquals(1209, $this->match->getTeamBEloNew());

        $this->assertEquals(1200, $this->match->getTeamAEloOld());
        $this->assertEquals(1210, $this->match->getTeamBEloOLd());

        $this->assertInstanceOf("Team", $this->match->getWinner());
        $this->assertInstanceOf("Team", $this->match->getLoser());
    }

    public function testEqualEloDraw()
    {
        $this->match = Match::enterMatch($this->team_a->getId(), $this->team_b->getId(), 3, 3, 30, $this->player_a->getId());

        $this->assertEquals(0, $this->match->getEloDiff());
        $this->assertEquals($this->match->getTeamAEloNew(), $this->match->getTeamBEloNew());
        $this->assertEquals($this->match->getTeamAEloOld(), $this->match->getTeamBEloOLd());
    }

    public function testShortMatch()
    {
        $this->match = Match::enterMatch($this->team_a->getId(), $this->team_b->getId(), 5, 2, 20, $this->player_a->getId());

        $this->assertEquals(20, $this->match->getDuration());

        $this->assertEquals(16, $this->match->getEloDiff());

        $this->assertEquals(1216, $this->match->getTeamAEloNew());
        $this->assertEquals(1184, $this->match->getTeamBEloNew());
    }

    public function testMiscMethods()
    {
        $old_matches = Match::getMatches();

        $this->match = Match::enterMatch($this->team_a->getId(), $this->team_b->getId(), 5, 2, 30, $this->player_a->getId());
        $this->match_b = Match::enterMatch($this->team_a->getId(), $this->team_b->getId(), 5, 2, 20, $this->player_b->getId());

        $this->assertEquals("now", $this->match->getTimestamp());

        $this->assertEquals($this->player_a->getId(), $this->match->getEnteredBy()->getId());

        $this->assertEquals(5, $this->match->getScore($this->team_a->getId()));
        $this->assertEquals(2, $this->match->getOpponentScore($this->team_a->getId()));

        $this->assertEquals($this->team_a->getId(), $this->match->getOpponent($this->team_b->getId())->getId());

        $matches = Match::getMatches();
        $this->assertArrayContainsModel($this->match, $matches);
        $this->assertArrayContainsModel($this->match_b, $matches);
        $this->assertEquals(2, count($matches) - count($old_matches));
    }

    public function tearDown()
    {
        $this->wipe($this->match, $this->match_b, $this->team_a, $this->team_b);
        parent::tearDown();
    }
}
