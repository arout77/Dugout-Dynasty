import React, { useRef, useEffect } from 'react';

export default function GameLog({ logs }) {
  // We attach a ref to the container to manage scrolling if needed,
  // although since we prepend logs, the user usually stays at the top.
  const containerRef = useRef(null);

  return (
    <div className="flex-grow bg-black bg-opacity-50 rounded-lg border border-gray-700 overflow-hidden flex flex-col shadow-inner min-h-[300px]">
      
      {/* Header */}
      <div className="bg-gray-800 px-4 py-2 text-xs font-bold uppercase text-gray-400 border-b border-gray-700 flex justify-between items-center">
        <span>Play-by-Play</span>
        <span className="text-[9px] bg-green-900 text-green-100 px-1.5 rounded animate-pulse">
          LIVE
        </span>
      </div>

      {/* Log List */}
      <div 
        ref={containerRef}
        className="flex-grow overflow-y-auto p-3 space-y-1.5 text-xs font-mono text-green-400 scroll-smooth max-h-[500px]"
      >
        {logs.map((line, index) => (
          <div 
            key={index} 
            // The first item (index 0) gets an animation to emphasize the new event
            className={`opacity-90 border-b border-gray-800/50 pb-1 ${index === 0 ? 'animate-in fade-in slide-in-from-top-2 duration-300 font-bold text-green-300' : ''}`}
          >
            {line}
          </div>
        ))}
        
        {logs.length === 0 && (
          <div className="text-gray-600 italic text-center mt-10">
            Waiting for first pitch...
          </div>
        )}
      </div>

    </div>
  );
}