import React from 'react';
import { motion } from 'framer-motion';
import * as FiIcons from 'react-icons/fi';
import { usePlayer } from '../context/PlayerContext';
import SafeIcon from '../common/SafeIcon';

const { FiTrendingUp, FiUsers, FiAward } = FiIcons;

const ConsensusRanking = () => {
  const { consensusRanking, rankings, players } = usePlayer();

  if (players.length === 0) {
    return (
      <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center py-12">
          <SafeIcon icon={FiUsers} className="mx-auto h-12 w-12 text-gray-400 mb-4" />
          <h3 className="text-lg font-medium text-gray-900 mb-2">No Data Available</h3>
          <p className="text-gray-500">
            Please upload players and collect rankings first.
          </p>
        </div>
      </div>
    );
  }

  if (consensusRanking.length === 0) {
    return (
      <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center py-12">
          <SafeIcon icon={FiTrendingUp} className="mx-auto h-12 w-12 text-gray-400 mb-4" />
          <h3 className="text-lg font-medium text-gray-900 mb-2">No Rankings Yet</h3>
          <p className="text-gray-500">
            Consensus rankings will appear once users submit their rankings.
          </p>
        </div>
      </div>
    );
  }

  const getRankBadgeColor = (rank) => {
    if (rank === 1) return 'bg-yellow-500 text-white';
    if (rank === 2) return 'bg-gray-400 text-white';
    if (rank === 3) return 'bg-amber-600 text-white';
    if (rank <= 10) return 'bg-nfl-blue text-white';
    return 'bg-gray-200 text-gray-700';
  };

  return (
    <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900 mb-2">Consensus Rankings</h1>
        <p className="text-gray-600">
          Aggregated rankings from {rankings.length} user submissions
        </p>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="bg-white rounded-lg shadow-sm p-6 border border-gray-200"
        >
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <SafeIcon icon={FiUsers} className="h-8 w-8 text-nfl-blue" />
            </div>
            <div className="ml-4">
              <div className="text-2xl font-bold text-gray-900">{rankings.length}</div>
              <div className="text-sm text-gray-500">Total Rankings</div>
            </div>
          </div>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.1 }}
          className="bg-white rounded-lg shadow-sm p-6 border border-gray-200"
        >
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <SafeIcon icon={FiTrendingUp} className="h-8 w-8 text-green-500" />
            </div>
            <div className="ml-4">
              <div className="text-2xl font-bold text-gray-900">{consensusRanking.length}</div>
              <div className="text-sm text-gray-500">Ranked Players</div>
            </div>
          </div>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.2 }}
          className="bg-white rounded-lg shadow-sm p-6 border border-gray-200"
        >
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <SafeIcon icon={FiAward} className="h-8 w-8 text-yellow-500" />
            </div>
            <div className="ml-4">
              <div className="text-2xl font-bold text-gray-900">
                {consensusRanking[0]?.averageScore.toFixed(1) || 'N/A'}
              </div>
              <div className="text-sm text-gray-500">Top Score</div>
            </div>
          </div>
        </motion.div>
      </div>

      {/* Rankings Table */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.3 }}
        className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden"
      >
        <div className="px-6 py-4 border-b border-gray-200">
          <h2 className="text-xl font-semibold text-gray-900">
            Consensus Rankings
          </h2>
        </div>
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Rank
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Player
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Team
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Opponent
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Avg Score
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {consensusRanking.map((player, index) => {
                const rank = index + 1;
                return (
                  <motion.tr
                    key={player.id}
                    initial={{ opacity: 0, x: -20 }}
                    animate={{ opacity: 1, x: 0 }}
                    transition={{ delay: index * 0.05 }}
                    className="hover:bg-gray-50"
                  >
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className={`inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold ${getRankBadgeColor(rank)}`}>
                        {rank}
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm font-medium text-gray-900">
                        {player.name}
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {player.team}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {player.opponent}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm font-medium text-gray-900">
                        {player.averageScore.toFixed(1)}
                      </div>
                    </td>
                  </motion.tr>
                );
              })}
            </tbody>
          </table>
        </div>
      </motion.div>

      {/* Methodology */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.4 }}
        className="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6"
      >
        <h3 className="text-lg font-medium text-blue-900 mb-3">Ranking Methodology</h3>
        <div className="text-sm text-blue-800 space-y-2">
          <p>• Each user ranking assigns points to players based on their position (higher position = more points)</p>
          <p>• Consensus ranking is calculated by averaging all user scores for each player</p>
          <p>• Players are then sorted by their average score in descending order</p>
          <p>• Only players that appear in user rankings are included in the consensus</p>
        </div>
      </motion.div>
    </div>
  );
};

export default ConsensusRanking;