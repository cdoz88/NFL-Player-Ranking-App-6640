import React from 'react';
import { Link, useLocation } from 'react-router-dom';
import * as FiIcons from 'react-icons/fi';
import SafeIcon from '../common/SafeIcon';

const { FiUsers, FiTrendingUp, FiBarChart3, FiCode } = FiIcons;

const Layout = ({ children }) => {
  const location = useLocation();

  const navItems = [
    { path: '/', label: 'Admin', icon: FiUsers },
    { path: '/rank', label: 'Rank Players', icon: FiTrendingUp },
    { path: '/consensus', label: 'Consensus', icon: FiBarChart3 },
    { path: '/embed', label: 'Embed', icon: FiCode }
  ];

  return (
    <div className="min-h-screen bg-gray-50">
      <nav className="bg-nfl-blue shadow-lg">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between h-16">
            <div className="flex items-center">
              <h1 className="text-xl font-bold text-white">NFL Player Rankings v4.2.0</h1>
            </div>
            <div className="flex space-x-8">
              {navItems.map((item) => {
                const isActive = location.pathname === item.path;
                return (
                  <Link
                    key={item.path}
                    to={item.path}
                    className={`inline-flex items-center px-1 pt-1 text-sm font-medium border-b-2 ${
                      isActive
                        ? 'border-white text-white'
                        : 'border-transparent text-gray-300 hover:text-white hover:border-gray-300'
                    } transition-colors duration-200`}
                  >
                    <SafeIcon icon={item.icon} className="mr-2" />
                    {item.label}
                  </Link>
                );
              })}
            </div>
          </div>
        </div>
      </nav>
      <main className="py-8">
        {children}
      </main>
    </div>
  );
};

export default Layout;