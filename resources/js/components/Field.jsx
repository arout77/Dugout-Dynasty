import React from 'react';

export default function Field({ state, pitcher, batter }) {
  // Safety check: If state is missing, don't render anything yet
  if (!state || !state.teams) return <div className="h-[600px] bg-gray-900 rounded-xl animate-pulse"></div>;

  const isTop = state.half === 'top';
  
  // Define defense positions based on who is fielding
  const defensiveTeam = isTop ? state.teams.home : state.teams.away;
  // Safety check
  if (!defensiveTeam) return null;

  // Map positions to coordinates
  const posCoords = {
    'C':  { x: 500, y: 860 },
    '1B': { x: 700, y: 450 },
    '2B': { x: 600, y: 350 },
    '3B': { x: 300, y: 450 },
    'SS': { x: 400, y: 350 },
    'LF': { x: 200, y: 250 },
    'CF': { x: 500, y: 150 },
    'RF': { x: 800, y: 250 },
    // Pitcher coordinate specifically for the defense loop (if P is in lineup array)
    'P':  { x: 500, y: 480 },
  };

  // Define style object outside of JSX to prevent syntax errors
  const textShadowStyle = { textShadow: '1px 1px 2px black' };

  // Helper to safely get last name
  const getLastName = (name) => {
    if (!name) return '';
    return name.split(' ').pop();
  };

  return (
    <div 
      className="relative bg-green-900 rounded-xl shadow-2xl overflow-hidden border-4 border-gray-700 w-full mx-auto"
      style={{ height: '600px' }}
    >
      <svg viewBox="0 0 1000 1000" className="w-full h-full absolute inset-0">
        {/* Field Grass */}
        <rect width="1000" height="1000" fill="#2d6a4f" />
        <path d="M 500 200 L 800 500 L 500 800 L 200 500 Z" fill="#9f6b48" />
        <path d="M 200 500 Q 500 900 800 500" fill="#9f6b48" />
        <line x1="500" y1="800" x2="100" y2="400" stroke="white" strokeWidth="4" />
        <line x1="500" y1="800" x2="900" y2="400" stroke="white" strokeWidth="4" />

        {/* Bases */}
        <rect x="485" y="185" width="30" height="30" fill="white" transform="rotate(45 500 200)" />
        <rect x="785" y="485" width="30" height="30" fill="white" transform="rotate(45 800 500)" />
        <rect x="185" y="485" width="30" height="30" fill="white" transform="rotate(45 200 500)" />
        <path d="M 500 800 L 515 815 L 515 830 L 485 830 L 485 815 Z" fill="white" />
        
        {/* Pitcher's Mound */}
        <circle cx="500" cy="500" r="40" fill="#9f6b48" stroke="#85573a" strokeWidth="2" />

        {/* Defense (Fielders) */}
        {defensiveTeam.lineup && defensiveTeam.lineup.map((player) => {
          if (!player) return null;
          // Skip Pitcher in this loop if we handle them separately to avoid duplicates
          if (player.position === 'P') return null; 

          const coords = posCoords[player.position];
          if (!coords) return null;
          return (
            <g key={player.player_id || Math.random()}>
              <circle cx={coords.x} cy={coords.y} r="8" fill="#1f2937" stroke="white" strokeWidth="1" />
              <text x={coords.x} y={coords.y + 20} fontSize="14" fill="white" textAnchor="middle" fontWeight="bold" style={textShadowStyle}>
                {player.position}
              </text>
              <text x={coords.x} y={coords.y + 35} fontSize="10" fill="#cbd5e1" textAnchor="middle" style={textShadowStyle}>
                {getLastName(player.player_name)}
              </text>
            </g>
          );
        })}

        {/* Explicit Pitcher Render (from 'pitcher' prop) */}
        {pitcher && (
            <g>
                <rect x="460" y="550" width="80" height="24" rx="4" fill="rgba(0,0,0,0.6)" stroke="#fbbf24" strokeWidth="1" />
                <text x="500" y="567" fontSize="14" fill="#fbbf24" textAnchor="middle" fontWeight="bold" style={{fontFamily: 'monospace'}}>
                    {getLastName(pitcher.player_name)}
                </text>
            </g>
        )}

        {/* Runners */}
        {[0, 1, 2].map((baseIndex) => {
          const runner = state.bases[baseIndex];
          // Coordinates for 1st, 2nd, 3rd
          const coords = [
            { x: 800, y: 500 }, // 1st
            { x: 500, y: 200 }, // 2nd
            { x: 200, y: 500 }  // 3rd
          ][baseIndex];

          if (!runner) return null;

          return (
            <g key={`runner-${baseIndex}`}>
              <circle cx={coords.x} cy={coords.y} r="15" fill="#facc15" stroke="black" strokeWidth="2" />
              <text x={coords.x} y={coords.y + 40} fontSize="14" fill="#facc15" textAnchor="middle" fontWeight="bold" style={textShadowStyle}>
                {getLastName(runner.name)}
              </text>
            </g>
          );
        })}
      </svg>

      {/* Info Overlay: Batter */}
      <div className="absolute bottom-4 left-4 bg-gray-800/90 p-3 rounded-lg border border-gray-600 shadow-xl">
          <div className="text-[10px] text-gray-400 uppercase font-bold tracking-wider">At Bat</div>
          <div className="text-lg font-bold text-white leading-tight">
            {batter ? batter.player_name : '...'}
          </div>
          <div className="text-xs text-gray-400 font-mono mt-0.5">
             AVG <span className="text-white font-bold">
               {batter ? parseFloat(batter.AVG||0).toFixed(3).replace(/^0+/, '') : '.000'}
             </span>
          </div>
      </div>
    </div>
  );
}