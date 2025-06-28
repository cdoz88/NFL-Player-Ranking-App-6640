import React from 'react';
import { HashRouter as Router, Routes, Route } from 'react-router-dom';
import { Toaster } from 'react-hot-toast';
import { PlayerProvider } from './context/PlayerContext';
import Layout from './components/Layout';
import AdminDashboard from './pages/AdminDashboard';
import UserRanking from './pages/UserRanking';
import ConsensusRanking from './pages/ConsensusRanking';
import EmbedRanking from './pages/EmbedRanking';
import EmbedWidget from './pages/EmbedWidget';

function App() {
  return (
    <PlayerProvider>
      <Router>
        <div className="min-h-screen bg-gray-50">
          <Routes>
            {/* Embedded widget route - no layout */}
            <Route path="/widget" element={<EmbedWidget />} />
            
            {/* Regular app routes with layout */}
            <Route path="/*" element={
              <Layout>
                <Routes>
                  <Route path="/" element={<AdminDashboard />} />
                  <Route path="/rank" element={<UserRanking />} />
                  <Route path="/consensus" element={<ConsensusRanking />} />
                  <Route path="/embed" element={<EmbedRanking />} />
                </Routes>
              </Layout>
            } />
          </Routes>
          <Toaster position="top-right" />
        </div>
      </Router>
    </PlayerProvider>
  );
}

export default App;