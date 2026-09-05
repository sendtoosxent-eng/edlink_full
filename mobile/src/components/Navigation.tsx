import { Text } from './Typography';
import React, { useEffect, useRef, useState } from 'react';
import { View, Pressable, StyleSheet, Animated, LayoutChangeEvent } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { colors, radius, shadows } from '../theme/index';
import type { AppTab } from '../screens/app/DashboardScreens';

interface TabBarProps {
  tabs: AppTab[];
  activeTab: AppTab;
  onSelect: (tab: AppTab) => void;
}

type IoniconsName = React.ComponentProps<typeof Ionicons>['name'];

const TAB_CONFIG: Record<AppTab, { label: string; activeIcon: IoniconsName; inactiveIcon: IoniconsName }> = {
  home: { label: 'Home', activeIcon: 'home', inactiveIcon: 'home-outline' },
  attendance: { label: 'Attendance', activeIcon: 'calendar', inactiveIcon: 'calendar-outline' },
  homework: { label: 'Homework', activeIcon: 'book', inactiveIcon: 'book-outline' },
  results: { label: 'Results', activeIcon: 'trophy', inactiveIcon: 'trophy-outline' },
  payments: { label: 'Payments', activeIcon: 'wallet', inactiveIcon: 'wallet-outline' },
  more: { label: 'Profile', activeIcon: 'person', inactiveIcon: 'person-outline' },
  notifications: { label: 'Notices', activeIcon: 'notifications', inactiveIcon: 'notifications-outline' },
  leave: { label: 'Leave', activeIcon: 'calendar-number', inactiveIcon: 'calendar-number-outline' },
  add_marks: { label: 'Add marks', activeIcon: 'create', inactiveIcon: 'create-outline' },
  view_marks: { label: 'Marks', activeIcon: 'reader', inactiveIcon: 'reader-outline' },
  teacher_results: { label: 'Results', activeIcon: 'trophy', inactiveIcon: 'trophy-outline' },
  add_homework: { label: 'Add work', activeIcon: 'add-circle', inactiveIcon: 'add-circle-outline' },
};

export function BottomTabBar({ tabs, activeTab, onSelect }: TabBarProps) {
  const insets = useSafeAreaInsets();
  const [containerWidth, setContainerWidth] = useState(0);
  
  const activeIndex = tabs.indexOf(activeTab);
  const slideAnim = useRef(new Animated.Value(activeIndex < 0 ? 0 : activeIndex)).current;

  // Calculate dynamic tab width based on actual container layout
  const horizontalPadding = 8;
  const availableWidth = containerWidth - horizontalPadding * 2;
  const tabWidth = tabs.length > 0 ? availableWidth / tabs.length : 0;

  useEffect(() => {
    if (activeIndex >= 0) {
      Animated.spring(slideAnim, {
        toValue: activeIndex,
        useNativeDriver: true,
        tension: 70,
        friction: 12,
      }).start();
    }
  }, [activeIndex, slideAnim]);

  const handleLayout = (e: LayoutChangeEvent) => {
    setContainerWidth(e.nativeEvent.layout.width);
  };

  const indicatorTranslateX = slideAnim.interpolate({
    inputRange: tabs.map((_, i) => i),
    outputRange: tabs.map((_, i) => i * tabWidth),
    extrapolate: 'clamp',
  });

  return (
    <View style={[styles.outerContainer, { paddingBottom: Math.max(insets.bottom, 12) }]}>
      <View style={styles.floatingBar} onLayout={handleLayout}>
        {/* Sliding Pill Background Indicator */}
        {tabWidth > 0 && (
          <Animated.View
            style={[
              styles.activeIndicator,
              {
                width: tabWidth - 6,
                transform: [{ translateX: indicatorTranslateX }],
              },
            ]}
          />
        )}

        {/* Tab Items */}
        {tabs.map((tab) => {
          const isActive = activeTab === tab;
          const config = TAB_CONFIG[tab];
          const iconName = isActive ? config.activeIcon : config.inactiveIcon;

          return (
            <TabButton
              key={tab}
              label={config.label}
              iconName={iconName}
              isActive={isActive}
              onPress={() => onSelect(tab)}
            />
          );
        })}
      </View>
    </View>
  );
}

// Sub-component for smooth press scale animations
function TabButton({
  label,
  iconName,
  isActive,
  onPress,
}: {
  label: string;
  iconName: IoniconsName;
  isActive: boolean;
  onPress: () => void;
}) {
  const scaleAnim = useRef(new Animated.Value(1)).current;

  const handlePressIn = () => {
    Animated.spring(scaleAnim, {
      toValue: 0.92,
      useNativeDriver: true,
      speed: 20,
    }).start();
  };

  const handlePressOut = () => {
    Animated.spring(scaleAnim, {
      toValue: 1,
      useNativeDriver: true,
      speed: 20,
    }).start();
  };

  return (
    <Pressable
      onPress={onPress}
      onPressIn={handlePressIn}
      onPressOut={handlePressOut}
      style={styles.tabItem}
      hitSlop={6}
    >
      <Animated.View style={[styles.tabContent, { transform: [{ scale: scaleAnim }] }]}>
        <Ionicons
          name={iconName}
          size={21}
          color={isActive ? 'navy' : '#efb000'}
        />
        <Text style={[styles.labelText, isActive ? styles.labelActive : styles.labelInactive]}>
          {label}
        </Text>
      </Animated.View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  outerContainer: {
    backgroundColor: 'transparent',
    paddingHorizontal: 16,
    paddingTop: 8,
  },
  floatingBar: {
    flexDirection: 'row',
    height: 66,
    backgroundColor: colors.primary, // Edlink dark navy background
    borderRadius: radius.lg ?? 24,
    paddingHorizontal: 8,
    alignItems: 'center',
    position: 'relative',
    ...shadows.card,
  },
  activeIndicator: {
    position: 'absolute',
    left: 11,
    height: 52,
    backgroundColor: colors.accent, // Highlight pill color
    borderRadius: radius.md ?? 16,
  },
  tabItem: {
    flex: 1,
    height: '100%',
    alignItems: 'center',
    justifyContent: 'center',
    zIndex: 1,
  },
  tabContent: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  labelText: {
    fontSize: 10,
    marginTop: 3,
    letterSpacing: 0.2,
  },
  labelActive: {
    color: 'navy',
    fontWeight: '800',
  },
  labelInactive: {
    color: '#efb000',
    fontWeight: '600',
  },
});
