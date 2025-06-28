import React from 'react';
import { motion } from 'framer-motion';
import * as FiIcons from 'react-icons/fi';
import { usePlayer } from '../context/PlayerContext';
import SafeIcon from '../common/SafeIcon';

const { FiTrendingUp, FiAward } = FiIcons;

const EmbedWidget = () => {
  const { consensusRanking } = usePlayer();

  if (consensusRanking.length === 0) {
    return (
      <div className="min-h-[400px] rounded-lg bg-white shadow-lg overflow-hidden">
        <div className="flex flex-col items-center justify-center h-64 p-6">
          <SafeIcon icon={FiTrendingUp} className="h-12 w-12 text-gray-400 mb-4" />
          <h3 className="text-lg font-medium text-gray-900 mb-2">No Rankings Available</h3>
          <p className="text-sm text-gray-500 text-center">
            Rankings will appear once users submit their votes.
          </p>
        </div>
      </div>
    );
  }

  return (
    <div className="rounded-lg bg-white shadow-lg overflow-hidden">
      <div className="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-600 to-blue-700">
        <div className="flex items-center justify-between">
          <div className="flex items-center text-white">
            <SafeIcon icon={FiAward} className="h-5 w-5 mr-2" />
            <h2 className="text-lg font-semibold">NFL Player Rankings</h2>
          </div>
          <div className="text-sm text-blue-100">
            Updated Live
          </div>
        </div>
      </div>

      <div className="divide-y divide-gray-100">
        {consensusRanking.slice(0, 10).map((player, index) => {
          const rank = index + 1;
          let rankColor = 'bg-blue-600';
          if (rank === 1) rankColor = 'bg-yellow-500';
          if (rank === 2) rankColor = 'bg-gray-400';
          if (rank === 3) rankColor = 'bg-amber-600';

          return (
            <motion.div
              key={player.id}
              initial={{ opacity: 0, x: -20 }}
              animate={{ opacity: 1, x: 0 }}
              transition={{ delay: index * 0.05 }}
              className="px-6 py-4 hover:bg-gray-50 transition-colors"
            >
              <div className="flex items-center justify-between">
                <div className="flex items-center space-x-4">
                  <div className={`w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold text-white ${rankColor}`}>
                    {rank}
                  </div>
                  <div>
                    <div className="font-medium text-gray-900">{player.name}</div>
                    <div className="text-sm text-gray-500">
                      {player.team} vs {player.opponent}
                    </div>
                  </div>
                </div>
                <div className="text-right">
                  <div className="font-semibold text-gray-900">
                    {player.averageScore.toFixed(1)}
                  </div>
                  <div className="text-xs text-gray-500">score</div>
                </div>
              </div>
            </motion.div>
          );
        })}
      </div>

      {consensusRanking.length > 10 && (
        <div className="px-6 py-3 text-center text-sm text-gray-500 border-t border-gray-200">
          +{consensusRanking.length - 10} more players
        </div>
      )}
    </div>
  );
};

export default EmbedWidget;