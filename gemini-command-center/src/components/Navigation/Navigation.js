import React from 'react';
import { NavLink, useLocation } from 'react-router-dom';

const Navigation = () => {
  const location = useLocation();

  const navItems = [
    {
      path: '/dashboard',
      label: 'Dashboard',
      icon: '🏠',
    },
    {
      path: '/settings',
      label: 'Settings',
      icon: '⚙️',
    },
    {
      path: '/seo',
      label: 'SEO Center',
      icon: '📈',
    },
    {
      path: '/content',
      label: 'Content',
      icon: '✍️',
    },
    {
      path: '/uiux',
      label: 'UI/UX',
      icon: '🎨',
    },
    {
      path: '/backup',
      label: 'Backup',
      icon: '💾',
    },
    {
      path: '/reports',
      label: 'Reports',
      icon: '📊',
    },
    {
      path: '/ai-agent',
      label: 'AI Agent',
      icon: '🤖',
    },
  ];

  return (
    <nav className="gcc-navigation">
      <ul className="gcc-nav-tabs">
        {navItems.map((item) => (
          <li key={item.path} className="gcc-nav-tab">
            <NavLink
              to={item.path}
              className={({ isActive }) =>
                `gcc-nav-link ${isActive || location.pathname.startsWith(item.path) ? 'active' : ''}`
              }
            >
              <span className="gcc-nav-icon">{item.icon}</span>
              <span className="gcc-nav-label">{item.label}</span>
            </NavLink>
          </li>
        ))}
      </ul>
    </nav>
  );
};

export default Navigation;