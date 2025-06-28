import React, { useState } from 'react';
import { motion } from 'framer-motion';
import * as FiIcons from 'react-icons/fi';
import toast from 'react-hot-toast';
import { usePlayer } from '../context/PlayerContext';
import SafeIcon from '../common/SafeIcon';

const { FiCode, FiCopy, FiExternalLink } = FiIcons;

const EmbedRanking = () => {
  const { consensusRanking } = usePlayer();

  const generateEmbedCode = () => {
    const baseUrl = window.location.origin + window.location.pathname;
    return `<iframe src="${baseUrl}#/widget" width="100%" height="600" frameborder="0" style="border-radius: 8px;box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);border: none;"> </iframe>`;
  };

  const copyToClipboard = () => {
    navigator.clipboard.writeText(generateEmbedCode()).then(() => {
      toast.success('Embed code copied to clipboard!');
    }).catch(() => {
      toast.error('Failed to copy embed code');
    });
  };

  const openPreview = () => {
    window.open('#/widget', '_blank');
  };

  if (consensusRanking.length === 0) {
    return (
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center py-12">
          <SafeIcon icon={FiCode} className="mx-auto h-12 w-12 text-gray-400 mb-4" />
          <h3 className="text-lg font-medium text-gray-900 mb-2">No Rankings to Embed</h3>
          <p className="text-gray-500">
            Create consensus rankings first to generate embed code.
          </p>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900 mb-2">Embed Rankings</h1>
        <p className="text-gray-600">
          Generate embed code to display consensus rankings on your website
        </p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {/* Actions */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="space-y-6"
        >
          <div className="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Actions</h3>
            <div className="space-y-3">
              <motion.button
                whileHover={{ scale: 1.02 }}
                whileTap={{ scale: 0.98 }}
                onClick={openPreview}
                className="w-full flex items-center justify-center px-4 py-2 bg-nfl-blue text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-nfl-blue focus:ring-offset-2"
              >
                <SafeIcon icon={FiExternalLink} className="mr-2" />
                Preview Embed
              </motion.button>
              <motion.button
                whileHover={{ scale: 1.02 }}
                whileTap={{ scale: 0.98 }}
                onClick={copyToClipboard}
                className="w-full flex items-center justify-center px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-nfl-blue focus:ring-offset-2"
              >
                <SafeIcon icon={FiCopy} className="mr-2" />
                Copy Embed Code
              </motion.button>
            </div>
          </div>
        </motion.div>

        {/* Embed Code */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.1 }}
        >
          <div className="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <h2 className="text-xl font-semibold text-gray-900 mb-4 flex items-center">
              <SafeIcon icon={FiCode} className="mr-2" />
              Embed Code
            </h2>
            <div className="bg-gray-900 rounded-lg p-4 overflow-x-auto">
              <pre className="text-green-400 text-sm whitespace-pre-wrap">
                {generateEmbedCode()}
              </pre>
            </div>
            <div className="mt-4 text-sm text-gray-600">
              <p className="mb-2">
                <strong>Usage Instructions:</strong>
              </p>
              <ul className="space-y-1 text-sm">
                <li>• Copy the embed code above</li>
                <li>• Paste it into your website's HTML</li>
                <li>• The iframe will display the current consensus rankings</li>
                <li>• Rankings update automatically when new data is submitted</li>
                <li>• Works on any website that supports iframes</li>
              </ul>
            </div>
          </div>

          {/* Enhanced Preview */}
          <div className="mt-6 bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Live Preview</h3>
            <div className="border border-gray-200 rounded-lg overflow-hidden bg-gray-50">
              <div className="p-2 bg-gray-100 text-center text-xs text-gray-500">
                This is how it will look on your website
              </div>
              <div className="p-4">
                <div className="bg-white text-gray-900 shadow-lg rounded-lg overflow-hidden">
                  <div className="px-4 py-3 border-b border-gray-200">
                    <div className="flex items-center justify-between">
                      <h4 className="font-semibold">NFL Player Rankings</h4>
                      <span className="text-xs text-gray-500">Live</span>
                    </div>
                  </div>
                  <div className="max-h-48 overflow-y-auto">
                    {consensusRanking.slice(0, 5).map((player, index) => (
                      <div key={player.id} className="px-4 py-3 border-b border-gray-100 last:border-b-0">
                        <div className="flex items-center justify-between">
                          <div className="flex items-center space-x-3">
                            <div className="w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold">
                              {index + 1}
                            </div>
                            <div>
                              <div className="text-sm font-medium">{player.name}</div>
                              <div className="text-xs text-gray-500">
                                {player.team} vs {player.opponent}
                              </div>
                            </div>
                          </div>
                          <div className="text-xs font-semibold">
                            {player.averageScore.toFixed(1)}
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </motion.div>
      </div>
    </div>
  );
};

export default EmbedRanking;