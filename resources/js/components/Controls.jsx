import React from 'react';

export default function Controls({ userIsBatting, onAction, disabled }) {
  const btnBase = "font-bold py-3 px-2 rounded border transition transform active:scale-95 flex items-center justify-center gap-2 text-sm";

  return (
    <div className="bg-gray-800 p-4 rounded-lg border border-gray-700 shadow-lg">
      <div className="grid grid-cols-2 gap-3">
        {/* PRIMARY ACTION: SIMULATE */}
        <button 
          onClick={() => onAction('normal')} 
          disabled={disabled}
          className={`col-span-2 ${btnBase} bg-green-600 hover:bg-green-500 text-white text-lg shadow-lg py-4`}>
          Simulate At-Bat
        </button>

        {userIsBatting ? (
          <>
            {/* OFFENSIVE CONTROLS */}
            <button onClick={() => onAction('bunt')} disabled={disabled} className={`${btnBase} bg-gray-700 hover:bg-gray-600 text-gray-200`}>
              Bunt
            </button>
            <button onClick={() => onAction('hit_run')} disabled={disabled} className={`${btnBase} bg-gray-700 hover:bg-gray-600 text-gray-200`}>
              Hit & Run
            </button>
            <button onClick={() => onAction('steal')} disabled={disabled} className={`${btnBase} bg-gray-700 hover:bg-gray-600 text-gray-200`}>
              Steal Base
            </button>
            <button onClick={() => alert('Pinch Hit feature coming soon!')} disabled={disabled} className={`${btnBase} bg-blue-800 hover:bg-blue-700 text-blue-100`}>
              Pinch Hit
            </button>
          </>
        ) : (
          <>
            {/* DEFENSIVE CONTROLS */}
            <button onClick={() => onAction('intentional_walk')} disabled={disabled} className={`col-span-1 ${btnBase} bg-blue-900 hover:bg-blue-800 text-blue-100`}>
               Intentional Walk
            </button>
            <button onClick={() => alert('Bullpen feature coming soon!')} disabled={disabled} className={`col-span-1 ${btnBase} bg-red-900 hover:bg-red-800 text-red-100`}>
               Call Bullpen
            </button>
          </>
        )}
      </div>
    </div>
  );
}