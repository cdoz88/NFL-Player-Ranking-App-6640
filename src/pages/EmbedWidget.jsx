import React, { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { motion } from 'framer-motion';
import * as FiIcons from 'react-icons/fi';
import { usePlayer } from '../context/PlayerContext';
import SafeIcon from '../common/SafeIcon';

const { FiTrendingUp, FiAward } = FiIcons;

const EmbedWidget = () => {
  const [searchParams] = useSearchParams();
  const { consensusRanking, players } = usePlayer();
  const [isLoading, setIsLoading] = useState(true);

  // Parse embed options from URL parameters
  const embedOptions = {
    showTeam: searchParams.get('showTeam') !== 'false',
    showOpponent: searchParams.get('showOpponent') !== 'false',
    maxPlayers: parseInt(searchParams.get('maxPlayers')) || 10,
    theme: searchParams.get('theme') || 'light',
    showHeader: searchParams.get('showHeader') !== 'false'
  };

  useEffect(() => {
    // Simulate loading time for better UX
    const timer = setTimeout(() => setIsLoading(false), 1000);
    return () => clearTimeout(timer);
  }, []);

  const getRankBadgeColor = (rank) => {
    if (embedOptions.theme === 'dark') {
      if (rank === 1) return 'bg-yellow-500 text-white';
      if (rank === 2) return 'bg-gray-300 text-gray-900';
      if (rank === 3) return 'bg-amber-600 text-white';
      if (rank <= 10) return 'bg-blue-600 text-white';
      return 'bg-gray-600 text-gray-200';
    } else if (embedOptions.theme === 'nfl') {
      if (rank === 1) return 'bg-yellow-500 text-white';
      if (rank === 2) return 'bg-gray-400 text-white';
      if (rank === 3) return 'bg-amber-600 text-white';
      return 'bg-nfl-blue text-white';
    } else {
      if (rank === 1) return 'bg-yellow-500 text-white';
      if (rank === 2) return 'bg-gray-400 text-white';
      if (rank === 3) return 'bg-amber-600 text-white';
      if (rank <= 10) return 'bg-blue-600 text-white';
      return 'bg-gray-200 text-gray-700';
    }
  };

  const getThemeClasses = () => {
    switch (embedOptions.theme) {
      case 'dark':
        return 'bg-gray-900 text-white';
      case 'nfl':
        return 'bg-gradient-to-br from-nfl-blue to-blue-800 text-white';
      default:
        return 'bg-white text-gray-900';
    }
  };

  const getHeaderClasses = () => {
    switch (embedOptions.theme) {
      case 'dark':
        return 'border-gray-700';
      case 'nfl':
        return 'border-blue-400';
      default:
        return 'border-gray-200';
    }
  };

  if (isLoading) {
    return (
      <div className={`min-h-[400px] rounded-lg shadow-lg overflow-hidden ${getThemeClasses()}`}>
        <div className="flex items-center justify-center h-64">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-current"></div>
          <span className="ml-3">Loading rankings...</span>
        </div>
      </div>
    );
  }

  if (consensusRanking.length === 0) {
    return (
      <div className={`min-h-[400px] rounded-lg shadow-lg overflow-hidden ${getThemeClasses()}`}>
        <div className="flex flex-col items-center justify-center h-64 p-6">
          <SafeIcon icon={FiTrendingUp} className="h-12 w-12 opacity-50 mb-4" />
          <h3 className="text-lg font-medium mb-2">No Rankings Available</h3>
          <p className="text-sm opacity-75 text-center">
            Rankings will appear once users submit their votes.
          </p>
        </div>
      </div>
    );
  }

  const displayedRankings = consensusRanking.slice(0, embedOptions.maxPlayers);

  return (
    <div className={`rounded-lg shadow-lg overflow-hidden ${getThemeClasses()}`}>
      {embedOptions.showHeader && (
        <div className={`px-6 py-4 border-b ${getHeaderClasses()}`}>
          <div className="flex items-center justify-between">
            <div className="flex items-center">
              <SafeIcon icon={FiAward} className="h-5 w-5 mr-2" />
              <h2 className="text-lg font-semibold">NFL Player Rankings</h2>
            </div>
            <div className="text-sm opacity-75">
              Updated Live
            </div>
          </div>
        </div>
      )}
      
      <div className="divide-y divide-opacity-20">
        {displayedRankings.map((player, index) => {
          const rank = index + 1;
          return (
            <motion.div
              key={player.id}
              initial={{ opacity: 0, x: -20 }}
              animate={{ opacity: 1, x: 0 }}
              transition={{ delay: index * 0.05 }}
              className="px-6 py-4 hover:bg-black hover:bg-opacity-5 transition-colors"
            >
              <div className="flex items-center justify-between">
                <div className="flex items-center space-x-4">
                  <div className={`w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold ${getRankBadgeColor(rank)}`}>
                    {rank}
                  </div>
                  <div>
                    <div className="font-medium">{player.name}</div>
                    {(embedOptions.showTeam || embedOptions.showOpponent) && (
                      <div className="text-sm opacity-75">
                        {embedOptions.showTeam && embedOptions.showOpponent && `${player.team} vs ${player.opponent}`}
                        {embedOptions.showTeam && !embedOptions.showOpponent && player.team}
                        {!embedOptions.showTeam && embedOptions.showOpponent && `vs ${player.opponent}`}
                      </div>
                    )}
                  </div>
                </div>
                <div className="text-right">
                  <div className="font-semibold">
                    {player.averageScore.toFixed(1)}
                  </div>
                  <div className="text-xs opacity-75">
                    score
                  </div>
                </div>
              </div>
            </motion.div>
          );
        })}
      </div>
      
      {consensusRanking.length > embedOptions.maxPlayers && (
        <div className={`px-6 py-3 text-center text-sm opacity-75 border-t ${getHeaderClasses()}`}>
          +{consensusRanking.length - embedOptions.maxPlayers} more players
        </div>
      )}
    </div>
  );
};

export default EmbedWidget;