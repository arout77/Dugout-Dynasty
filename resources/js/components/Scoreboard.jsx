import React from 'react';

export default function Scoreboard({ state }) {
  const isTop = state.half === 'top';

  // Helper to render "lights" for the count
  // activeCondition: boolean to determine if light is on
  // colorClass: Tailwind class for the "on" state
  const CountLight = ({ active, colorClass = 'bg-gray-700' }) => (
    <div className={`w-2.5 h-2.5 rounded-full transition-colors duration-300 ${active ? colorClass : 'bg-gray-700'}`} />
  );

  return (
    <div className="bg-gray-800 border-b border-gray-700 p-2 shadow-lg z-50">
      <div className="container mx-auto max-w-6xl flex justify-between items-center">
        
        {/* --- TEAMS & SCORE --- */}
        <div className="flex items-center gap-4 bg-black rounded px-4 py-1 border border-gray-600">
          {/* Away Team */}
          <div className="text-center w-16">
            <div className="text-[10px] text-gray-400 font-bold uppercase truncate" title={state.teams.away.name}>
              {state.teams.away.name}
            </div>
            <div className="text-3xl font-mono font-bold text-yellow-400">
              {state.score.away}
            </div>
          </div>

          <div className="text-gray-600 text-xl font-thin">-</div>

          {/* Home Team */}
          <div className="text-center w-16">
            <div className="text-[10px] text-gray-400 font-bold uppercase truncate" title={state.teams.home.name}>
              {state.teams.home.name}
            </div>
            <div className="text-3xl font-mono font-bold text-yellow-400">
              {state.score.home}
            </div>
          </div>
        </div>

        {/* --- INNING & COUNT --- */}
        <div className="flex items-center gap-6">
          
          {/* Inning Indicator */}
          <div className="flex flex-col items-center w-16">
            <div className="text-[10px] text-gray-400 uppercase">Inn</div>
            <div className="flex items-center gap-1 text-xl font-bold">
              <span className="text-yellow-500 text-sm">
                {isTop ? '▲' : '▼'}
              </span>
              <span>{state.inning}</span>
            </div>
          </div>

          {/* Count Lights (B-S-O) */}
          <div className="grid grid-cols-3 gap-x-4 gap-y-1">
            
            {/* Balls (Placeholder for future pitch-by-sim) */}
            <div className="text-[9px] text-gray-500 uppercase text-right">B</div>
            <div className="col-span-2 flex gap-1 items-center">
              <CountLight active={false} colorClass="bg-green-500" />
              <CountLight active={false} colorClass="bg-green-500" />
              <CountLight active={false} colorClass="bg-green-500" />
            </div>

            {/* Strikes (Placeholder) */}
            <div className="text-[9px] text-gray-500 uppercase text-right">S</div>
            <div className="col-span-2 flex gap-1 items-center">
               <CountLight active={false} colorClass="bg-red-500" />
               <CountLight active={false} colorClass="bg-red-500" />
            </div>

            {/* Outs */}
            <div className="text-[9px] text-gray-500 uppercase text-right">O</div>
            <div className="col-span-2 flex gap-1 items-center">
              {/* Show 2 lights. If outs >= 1, first is lit. If outs >= 2, second is lit. */}
              <CountLight active={state.outs >= 1} colorClass="bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.8)]" />
              <CountLight active={state.outs >= 2} colorClass="bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.8)]" />
            </div>

          </div>
        </div>

      </div>
    </div>
  );
}