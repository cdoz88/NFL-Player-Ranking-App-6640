import React, { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { DndContext, closestCenter, KeyboardSensor, PointerSensor, useSensor, useSensors } from '@dnd-kit/core';
import { arrayMove, SortableContext, sortableKeyboardCoordinates, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import * as FiIcons from 'react-icons/fi';
import toast from 'react-hot-toast';
import { usePlayer } from '../context/PlayerContext';
import SafeIcon from '../common/SafeIcon';

const { FiMove, FiSave, FiUser } = FiIcons;

const SortablePlayer = ({ player, rank }) => {
  const {
    attributes,
    listeners,
    setNodeRef,
    transform,
    transition,
    isDragging
  } = useSortable({ id: player.id });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
  };

  return (
    <motion.div
      ref={setNodeRef}
      style={style}
      {...attributes}
      {...listeners}
      className={`bg-white rounded-lg shadow-sm border border-gray-200 p-4 cursor-move hover:shadow-md transition-shadow ${
        isDragging ? 'opacity-50' : ''
      }`}
      whileHover={{ scale: 1.01 }}
      layout
    >
      <div className="flex items-center justify-between">
        <div className="flex items-center space-x-3">
          <div className="flex-shrink-0">
            <div className="w-8 h-8 bg-nfl-blue text-white rounded-full flex items-center justify-center text-sm font-bold">
              {rank}
            </div>
          </div>
          <div>
            <h3 className="text-sm font-medium text-gray-900">{player.name}</h3>
            <p className="text-xs text-gray-500">
              {player.team} vs {player.opponent}
            </p>
          </div>
        </div>
        <SafeIcon icon={FiMove} className="text-gray-400" />
      </div>
    </motion.div>
  );
};

const UserRanking = () => {
  const { players, submitRanking } = usePlayer();
  const [rankedPlayers, setRankedPlayers] = useState([]);
  const [userId, setUserId] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  const sensors = useSensors(
    useSensor(PointerSensor),
    useSensor(KeyboardSensor, {
      coordinateGetter: sortableKeyboardCoordinates,
    })
  );

  useEffect(() => {
    setRankedPlayers([...players]);
  }, [players]);

  const handleDragEnd = (event) => {
    const { active, over } = event;

    if (active.id !== over?.id) {
      setRankedPlayers((items) => {
        const oldIndex = items.findIndex(item => item.id === active.id);
        const newIndex = items.findIndex(item => item.id === over.id);
        return arrayMove(items, oldIndex, newIndex);
      });
    }
  };

  const handleSubmit = async () => {
    if (!userId.trim()) {
      toast.error('Please enter your name');
      return;
    }

    if (rankedPlayers.length === 0) {
      toast.error('No players to rank');
      return;
    }

    setIsSubmitting(true);
    
    try {
      const ranking = rankedPlayers.map(player => player.id);
      submitRanking(ranking, userId.trim());
      toast.success('Ranking submitted successfully!');
      setUserId('');
    } catch (error) {
      toast.error('Error submitting ranking');
    } finally {
      setIsSubmitting(false);
    }
  };

  if (players.length === 0) {
    return (
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center py-12">
          <SafeIcon icon={FiUser} className="mx-auto h-12 w-12 text-gray-400 mb-4" />
          <h3 className="text-lg font-medium text-gray-900 mb-2">No Players Available</h3>
          <p className="text-gray-500">
            Please ask an admin to upload player data first.
          </p>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900 mb-2">Rank NFL Players</h1>
        <p className="text-gray-600">
          Drag and drop players to create your personal ranking
        </p>
      </div>

      {/* User Input */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        className="bg-white rounded-lg shadow-sm p-6 border border-gray-200 mb-8"
      >
        <div className="flex flex-col sm:flex-row gap-4 items-end">
          <div className="flex-1">
            <label htmlFor="userId" className="block text-sm font-medium text-gray-700 mb-2">
              Your Name
            </label>
            <input
              type="text"
              id="userId"
              value={userId}
              onChange={(e) => setUserId(e.target.value)}
              placeholder="Enter your name..."
              className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-nfl-blue focus:border-nfl-blue"
            />
          </div>
          <motion.button
            whileHover={{ scale: 1.02 }}
            whileTap={{ scale: 0.98 }}
            onClick={handleSubmit}
            disabled={isSubmitting || !userId.trim()}
            className="px-6 py-2 bg-nfl-blue text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-nfl-blue focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
          >
            {isSubmitting ? (
              <>
                <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                Submitting...
              </>
            ) : (
              <>
                <SafeIcon icon={FiSave} className="mr-2" />
                Submit Ranking
              </>
            )}
          </motion.button>
        </div>
      </motion.div>

      {/* Ranking Interface */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.1 }}
        className="bg-white rounded-lg shadow-sm border border-gray-200 p-6"
      >
        <div className="flex items-center justify-between mb-6">
          <h2 className="text-xl font-semibold text-gray-900">
            Your Ranking ({rankedPlayers.length} players)
          </h2>
          <div className="text-sm text-gray-500">
            Drag to reorder
          </div>
        </div>

        <DndContext
          sensors={sensors}
          collisionDetection={closestCenter}
          onDragEnd={handleDragEnd}
        >
          <SortableContext
            items={rankedPlayers}
            strategy={verticalListSortingStrategy}
          >
            <div className="space-y-3">
              {rankedPlayers.map((player, index) => (
                <SortablePlayer
                  key={player.id}
                  player={player}
                  rank={index + 1}
                />
              ))}
            </div>
          </SortableContext>
        </DndContext>
      </motion.div>

      {/* Instructions */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.2 }}
        className="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4"
      >
        <h3 className="text-sm font-medium text-blue-900 mb-2">How to rank:</h3>
        <ul className="text-sm text-blue-800 space-y-1">
          <li>• Drag and drop players to reorder them</li>
          <li>• Position 1 is the highest ranked player</li>
          <li>• Enter your name and submit when finished</li>
          <li>• Your ranking will contribute to the consensus ranking</li>
        </ul>
      </motion.div>
    </div>
  );
};

export default UserRanking;