import React from 'react';
import ReactDOM from 'react-dom/client';
import GameInterface from './components/GameInterface';

const rootElement = document.getElementById('react-root');

if (rootElement) {
  // We hydrate the state from a global variable set in Twig
  const initialState = window.GAME_DATA;
  const userTeamId = window.USER_TEAM_ID;
  const baseUrl = window.BASE_URL;

  const root = ReactDOM.createRoot(rootElement);
  root.render(
    <React.StrictMode>
      <GameInterface 
        initialState={initialState} 
        userTeamId={userTeamId} 
        baseUrl={baseUrl} 
      />
    </React.StrictMode>
  );
}