import React, { useState } from 'react';
import { motion } from 'framer-motion';
import * as FiIcons from 'react-icons/fi';
import Papa from 'papaparse';
import toast from 'react-hot-toast';
import { usePlayer } from '../context/PlayerContext';
import SafeIcon from '../common/SafeIcon';

const { FiUpload, FiTrash2, FiUsers, FiFileText } = FiIcons;

const AdminDashboard = () => {
  const { players, rankings, addPlayers, clearAllData } = usePlayer();
  const [uploading, setUploading] = useState(false);

  const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    if (file.type !== 'text/csv') {
      toast.error('Please upload a CSV file');
      return;
    }

    setUploading(true);

    Papa.parse(file, {
      header: true,
      skipEmptyLines: true,
      transformHeader: (header) => {
        // Normalize headers: lowercase and trim whitespace
        return header.toLowerCase().trim();
      },
      complete: (results) => {
        try {
          console.log('Parsed CSV data:', results.data);
          console.log('Headers found:', results.meta.fields);

          if (!results.data || results.data.length === 0) {
            toast.error('CSV file appears to be empty');
            setUploading(false);
            return;
          }

          // Check if we have the required headers (case insensitive)
          const headers = results.meta.fields || [];
          const hasName = headers.some(h => h.includes('name'));
          const hasTeam = headers.some(h => h.includes('team'));
          const hasOpponent = headers.some(h => h.includes('opponent'));

          if (!hasName || !hasTeam || !hasOpponent) {
            toast.error(`Missing required columns. Found: ${headers.join(', ')}. Required: name, team, opponent`);
            setUploading(false);
            return;
          }

          const parsedPlayers = results.data
            .filter(row => {
              // Filter out completely empty rows
              const values = Object.values(row);
              return values.some(value => value && value.toString().trim());
            })
            .map((row, index) => {
              // Find the correct column names (case insensitive)
              const nameCol = Object.keys(row).find(key => key.includes('name'));
              const teamCol = Object.keys(row).find(key => key.includes('team'));
              const opponentCol = Object.keys(row).find(key => key.includes('opponent'));

              return {
                id: `player-${Date.now()}-${index}`,
                name: row[nameCol]?.toString().trim() || '',
                team: row[teamCol]?.toString().trim() || '',
                opponent: row[opponentCol]?.toString().trim() || ''
              };
            })
            .filter(player => {
              // Only keep players with all required fields
              return player.name && player.team && player.opponent;
            });

          console.log('Processed players:', parsedPlayers);

          if (parsedPlayers.length === 0) {
            toast.error('No valid player data found. Please ensure all rows have name, team, and opponent values.');
            setUploading(false);
            return;
          }

          addPlayers(parsedPlayers);
          toast.success(`Successfully uploaded ${parsedPlayers.length} players`);
        } catch (error) {
          console.error('Error parsing CSV:', error);
          toast.error('Error parsing CSV file');
        } finally {
          setUploading(false);
        }
      },
      error: (error) => {
        console.error('Papa Parse error:', error);
        toast.error('Error reading CSV file');
        setUploading(false);
      }
    });

    // Reset input
    event.target.value = '';
  };

  const handleClearData = () => {
    if (window.confirm('Are you sure you want to clear all data? This cannot be undone.')) {
      clearAllData();
      toast.success('All data cleared successfully');
    }
  };

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900 mb-2">Admin Dashboard</h1>
        <p className="text-gray-600">Upload CSV files of NFL players and manage rankings</p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Upload Section */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="lg:col-span-2"
        >
          <div className="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <h2 className="text-xl font-semibold text-gray-900 mb-4 flex items-center">
              <SafeIcon icon={FiUpload} className="mr-2" />
              Upload Player Data
            </h2>
            
            <div className="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-nfl-blue transition-colors">
              <SafeIcon icon={FiFileText} className="mx-auto h-12 w-12 text-gray-400 mb-4" />
              <div className="space-y-2">
                <h3 className="text-lg font-medium text-gray-900">Upload CSV File</h3>
                <p className="text-gray-500">
                  CSV must contain columns: <strong>name</strong>, <strong>team</strong>, <strong>opponent</strong>
                </p>
                <p className="text-sm text-gray-400">
                  Column names are case-insensitive (Name, NAME, name all work)
                </p>
              </div>
              
              <div className="mt-6">
                <label className="relative cursor-pointer">
                  <input
                    type="file"
                    accept=".csv"
                    onChange={handleFileUpload}
                    className="sr-only"
                    disabled={uploading}
                  />
                  <motion.div
                    whileHover={{ scale: 1.02 }}
                    whileTap={{ scale: 0.98 }}
                    className="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-nfl-blue hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-nfl-blue disabled:opacity-50"
                  >
                    {uploading ? (
                      <>
                        <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                        Uploading...
                      </>
                    ) : (
                      <>
                        <SafeIcon icon={FiUpload} className="mr-2" />
                        Choose CSV File
                      </>
                    )}
                  </motion.div>
                </label>
              </div>
            </div>

            {/* Sample CSV Format */}
            <div className="mt-6 p-4 bg-gray-50 rounded-lg">
              <h4 className="font-medium text-gray-900 mb-2">Sample CSV Format:</h4>
              <pre className="text-sm text-gray-600 bg-white p-3 rounded border">
{`name,team,opponent
Josh Allen,Buffalo Bills,Miami Dolphins
Cooper Kupp,Los Angeles Rams,Arizona Cardinals
Jonathan Taylor,Indianapolis Colts,Tennessee Titans
Davante Adams,Las Vegas Raiders,Kansas City Chiefs`}
              </pre>
              <div className="mt-2 text-xs text-gray-500">
                <strong>Note:</strong> Column names can be in any case (Name, NAME, name, etc.)
              </div>
            </div>
          </div>
        </motion.div>

        {/* Stats Section */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.1 }}
          className="space-y-6"
        >
          {/* Current Stats */}
          <div className="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Current Stats</h3>
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <div className="flex items-center">
                  <SafeIcon icon={FiUsers} className="text-nfl-blue mr-2" />
                  <span className="text-gray-600">Players</span>
                </div>
                <span className="text-2xl font-bold text-gray-900">{players.length}</span>
              </div>
              <div className="flex items-center justify-between">
                <div className="flex items-center">
                  <SafeIcon icon={FiFileText} className="text-green-500 mr-2" />
                  <span className="text-gray-600">Rankings</span>
                </div>
                <span className="text-2xl font-bold text-gray-900">{rankings.length}</span>
              </div>
            </div>
          </div>

          {/* Actions */}
          <div className="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Actions</h3>
            <motion.button
              whileHover={{ scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
              onClick={handleClearData}
              className="w-full flex items-center justify-center px-4 py-2 border border-red-300 text-red-700 rounded-md hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
            >
              <SafeIcon icon={FiTrash2} className="mr-2" />
              Clear All Data
            </motion.button>
          </div>
        </motion.div>
      </div>

      {/* Players Preview */}
      {players.length > 0 && (
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.2 }}
          className="mt-8"
        >
          <div className="bg-white rounded-lg shadow-sm border border-gray-200">
            <div className="px-6 py-4 border-b border-gray-200">
              <h3 className="text-lg font-semibold text-gray-900">
                Current Players ({players.length})
              </h3>
            </div>
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Name
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Team
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Opponent
                    </th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {players.slice(0, 10).map((player) => (
                    <tr key={player.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {player.name}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {player.team}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {player.opponent}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
              {players.length > 10 && (
                <div className="px-6 py-3 bg-gray-50 text-sm text-gray-500 text-center">
                  And {players.length - 10} more players...
                </div>
              )}
            </div>
          </div>
        </motion.div>
      )}
    </div>
  );
};

export default AdminDashboard;