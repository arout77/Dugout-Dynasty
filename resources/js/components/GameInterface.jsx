import React, { useState, useEffect } from 'react';
import Scoreboard from './Scoreboard';
import Field from './Field';
import Controls from './Controls';
import GameLog from './GameLog';

export default function GameInterface({ initialState, userTeamId, baseUrl }) {
  const [state, setState] = useState(initialState.state);
  const [batter, setBatter] = useState(initialState.batter);
  const [pitcher, setPitcher] = useState(initialState.pitcher);
  const [loading, setLoading] = useState(false);

  // Helper: Who is batting?
  const isTop = state.half === 'top';
  const battingTeamId = isTop ? state.teams.away.id : state.teams.home.id;
  const pitchingTeamId = isTop ? state.teams.home.id : state.teams.away.id;
  const userIsBatting = userTeamId === battingTeamId;

  const handleSim = async (action) => {
    if (loading) return;
    setLoading(true);

    try {
      const response = await fetch(`${baseUrl}/api/game/sim-at-bat`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action }),
      });
      const data = await response.json();

      if (data.error) {
        alert(data.error);
      } else {
        setState(data.state);
        if (data.game_over) {
            window.location.href = `${baseUrl}/game/boxscore/${data.state.game_id}`;
        }
        if (data.next_batter) setBatter(data.next_batter);
        if (data.next_pitcher) setPitcher(data.next_pitcher);
        
        if (data.game_over) {
            alert("Game Over!");
            window.location.href = `${baseUrl}/dashboard`;
        }
      }
    } catch (error) {
      console.error("Simulation failed:", error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gray-900 text-white font-sans flex flex-col">
      <Scoreboard state={state} />

      <div className="container mx-auto max-w-7xl p-4 grid grid-cols-1 lg:grid-cols-12 gap-4 flex-grow">
        
        {/* Left: Field & Action */}
        <div className="lg:col-span-8 flex flex-col gap-4">
          <Field 
            state={state} 
            pitcher={pitcher} 
            batter={batter}
          />
        </div>

        {/* Right: Controls & Logs */}
        <div className="lg:col-span-4 flex flex-col gap-4 h-full">
          <Controls 
            userIsBatting={userIsBatting} 
            onAction={handleSim} 
            disabled={loading || state.game_over} 
          />
          <GameLog logs={state.log} />
        </div>
      </div>
    </div>
  );
}